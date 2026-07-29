<?php

namespace Modules\Sandbox\Tests\Feature;

use App\Services\Registry\HookInboundProcessorRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\Core\Models\User;
use Modules\Hook\Http\Controllers\HookInboundController;
use Modules\Hook\Models\HookInbound;
use Modules\Hook\Models\HookInboundRequest;
use Modules\Sandbox\Jobs\ApplyInventoryAdjustmentJob;
use Modules\Sandbox\Models\SandboxIntake;
use Modules\Sandbox\Models\SandboxInventoryLevel;
use Modules\Sandbox\Processors\SandboxInboundProcessor;
use Modules\Sandbox\Services\Handlers\SandboxInboundReceivedHandler;
use Modules\Sandbox\Services\SandboxPayloadProcessingService;
use Tests\TestCase;

class SandboxProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--path' => 'Modules/Hook/database/migrations',
            '--realpath' => false,
        ])->assertSuccessful();
        $this->artisan('migrate', [
            '--path' => 'Modules/Sandbox/database/migrations',
            '--realpath' => false,
        ])->assertSuccessful();

        config([
            'sandbox.probe_header' => 'X-Sandbox-Probe',
            'sandbox.token_header' => 'X-Sandbox-Token',
            'sandbox.token' => 'test-sandbox-token',
            'sandbox.apply_queue' => 'custom-sandbox-apply',
            'sandbox.review_queue' => 'custom-sandbox-review',
            'sandbox.cache_key' => 'sandbox:test:entries',
            'sandbox.max_entries' => 50,
        ]);

        app(HookInboundProcessorRegistry::class)->register(new SandboxInboundProcessor);

        Route::post('/sandbox-hook-test/{slug}', [HookInboundController::class, 'receive'])
            ->name('sandbox.hook.receive');
        Route::get('/sandbox-test', fn () => null)->name('sandbox.index');
        Route::getRoutes()->refreshNameLookups();
    }

    public function test_probe_is_rejected_when_inbound_token_is_not_configured(): void
    {
        config(['sandbox.token' => null]);

        $request = Request::create('/api/v1/hook/inbound/test', 'POST');
        $request->headers->set('X-Sandbox-Probe', '1');

        $response = (new SandboxInboundProcessor)->verify($request, new HookInbound);

        $this->assertNotNull($response);
        $this->assertSame(503, $response->getStatusCode());
    }

    public function test_verified_token_is_removed_before_hook_history_is_persisted(): void
    {
        Queue::fake();

        $inbound = $this->createInbound();

        $response = $this->withHeaders([
            'X-Sandbox-Probe' => '1',
            'x-SaNdBoX-tOkEn' => 'test-sandbox-token',
        ])->postJson(route('sandbox.hook.receive', $inbound->slug), $this->payload((string) Str::uuid()));

        $response->assertOk();

        $headers = $inbound->inboundRequests()->sole()->headers;

        $this->assertArrayNotHasKey('x-sandbox-token', array_change_key_case($headers, CASE_LOWER));
    }

    public function test_duplicate_correlation_id_reuses_intake_and_dispatches_once_on_configured_queue(): void
    {
        Queue::fake();

        $inbound = $this->createInbound();
        $correlationId = (string) Str::uuid();
        $firstRequest = $this->createInboundRequest($inbound, $this->payload($correlationId));
        $duplicateRequest = $this->createInboundRequest($inbound, $this->payload($correlationId));
        $handler = app(SandboxInboundReceivedHandler::class);

        $handler->handle(['request' => $firstRequest]);
        $handler->handle(['request' => $duplicateRequest]);

        $this->assertSame(1, SandboxIntake::query()
            ->where('inbound_id', $inbound->id)
            ->where('correlation_id', $correlationId)
            ->count());
        $this->assertSame($firstRequest->id, SandboxIntake::query()->firstOrFail()->inbound_request_id);
        Queue::assertPushedOn('custom-sandbox-apply', ApplyInventoryAdjustmentJob::class);
        Queue::assertPushed(ApplyInventoryAdjustmentJob::class, 1);
    }

    public function test_same_correlation_id_can_be_processed_for_separate_users_and_inbounds(): void
    {
        Queue::fake();

        $firstInbound = $this->createInbound();
        $secondInbound = $this->createInbound();
        $correlationId = (string) Str::uuid();
        $handler = app(SandboxInboundReceivedHandler::class);

        $handler->handle([
            'request' => $this->createInboundRequest($firstInbound, $this->payload($correlationId)),
        ]);
        $handler->handle([
            'request' => $this->createInboundRequest($secondInbound, $this->payload($correlationId)),
        ]);

        $this->assertNotSame($firstInbound->user_id, $secondInbound->user_id);
        $this->assertSame(2, SandboxIntake::query()->where('correlation_id', $correlationId)->count());
        $this->assertSame(1, SandboxIntake::query()
            ->where('inbound_id', $firstInbound->id)
            ->where('correlation_id', $correlationId)
            ->count());
        $this->assertSame(1, SandboxIntake::query()
            ->where('inbound_id', $secondInbound->id)
            ->where('correlation_id', $correlationId)
            ->count());
        Queue::assertPushed(ApplyInventoryAdjustmentJob::class, 2);
    }

    public function test_payload_routing_uses_configured_review_queue(): void
    {
        $payload = $this->payload((string) Str::uuid());
        $payload['items'][0]['delta'] = 100;

        $processing = app(SandboxPayloadProcessingService::class)->process($payload);

        $this->assertSame('custom-sandbox-review', $processing['output']['routing']['queue']);
    }

    public function test_inventory_adjustment_is_applied_once_and_preserves_subsequent_updates(): void
    {
        $inbound = $this->createInbound();
        $request = $this->createInboundRequest($inbound, $this->payload((string) Str::uuid()));
        $level = SandboxInventoryLevel::create([
            'user_id' => $inbound->user_id,
            'warehouse_code' => 'WH-01',
            'sku' => 'SKU-001',
            'quantity' => 10,
        ]);
        $intake = SandboxIntake::create([
            'user_id' => $inbound->user_id,
            'inbound_id' => $inbound->id,
            'inbound_request_id' => $request->id,
            'event' => 'inventory.adjustment',
            'correlation_id' => Str::uuid(),
            'warehouse_code' => 'WH-01',
            'status' => 'queued',
            'normalized_items' => [[
                'sku' => 'SKU-001',
                'delta' => 5,
                'unit_cost' => 10,
            ]],
        ]);

        $job = new ApplyInventoryAdjustmentJob($intake->id);
        $job->handle();
        $level->increment('quantity', 2);
        $job->handle();

        $this->assertSame(17, $level->refresh()->quantity);
        $this->assertSame('applied', $intake->refresh()->status);
        $this->assertNotNull($intake->applied_at);
    }

    private function createInbound(): HookInbound
    {
        return HookInbound::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Sandbox Test',
            'slug' => Str::lower(Str::random(12)),
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function createInboundRequest(HookInbound $inbound, array $payload): HookInboundRequest
    {
        return HookInboundRequest::create([
            'inbound_id' => $inbound->id,
            'method' => 'POST',
            'url' => 'https://example.test/api/v1/hook/inbound/'.$inbound->slug,
            'headers' => [
                'x-sandbox-probe' => '1',
            ],
            'payload' => $payload,
            'source_ip' => '127.0.0.1',
            'content_type' => 'application/json',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $correlationId): array
    {
        return [
            'event' => 'inventory.adjustment',
            'correlation_id' => $correlationId,
            'occurred_at' => now()->toIso8601String(),
            'warehouse' => ['code' => 'WH-01'],
            'items' => [[
                'sku' => 'SKU-001',
                'delta' => 5,
                'unit_cost' => 10,
            ]],
        ];
    }
}
