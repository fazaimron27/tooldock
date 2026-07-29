<?php

namespace Modules\Hook\Tests\Feature;

use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Modules\Core\Models\User;
use Modules\Hook\Http\Requests\StoreOutboundRequest;
use Modules\Hook\Http\Requests\UpdateOutboundRequest;
use Modules\Hook\Jobs\SendOutboundWebhookJob;
use Modules\Hook\Models\HookInbound;
use Modules\Hook\Models\HookOutbound;
use Modules\Hook\Models\HookOutboundDelivery;
use Modules\Hook\Observers\HookModelObserver;
use Modules\Hook\Services\HookDashboardService;
use Modules\Hook\Services\HookEventRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HookSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('hook_inbounds')) {
            $this->artisan('migrate', [
                '--path' => 'Modules/Hook/database/migrations',
                '--no-interaction' => true,
            ])->assertSuccessful();
        }
    }

    public function test_observer_only_dispatches_outbounds_owned_by_the_source_models_user(): void
    {
        $sourceOwner = User::factory()->create();
        $currentUser = User::factory()->create();
        $sourceOutbound = $this->createOutbound($sourceOwner, 'test.source_created');
        $this->createOutbound($currentUser, 'test.source_created');

        $this->actingAs($currentUser);
        Queue::fake();

        $registry = new HookEventRegistry;
        $registry->register(
            key: 'test.source_created',
            label: 'Test Source Created',
            modelClass: DirectlyOwnedHookSource::class,
        );

        (new HookModelObserver($registry))->created(new DirectlyOwnedHookSource([
            'user_id' => $sourceOwner->id,
        ]));

        Queue::assertPushed(SendOutboundWebhookJob::class, 1);
        Queue::assertPushed(
            SendOutboundWebhookJob::class,
            fn (SendOutboundWebhookJob $job): bool => $job->outbound->is($sourceOutbound)
                && $job->userId === $sourceOwner->id,
        );
    }

    public function test_observer_uses_the_current_user_for_an_indirectly_owned_source_model(): void
    {
        $currentUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $currentOutbound = $this->createOutbound($currentUser, 'test.indirect_source_created');
        $this->createOutbound($otherUser, 'test.indirect_source_created');

        $this->actingAs($currentUser);
        Queue::fake();

        $registry = new HookEventRegistry;
        $registry->register(
            key: 'test.indirect_source_created',
            label: 'Test Indirect Source Created',
            modelClass: IndirectlyOwnedHookSource::class,
        );

        (new HookModelObserver($registry))->created(new IndirectlyOwnedHookSource);

        Queue::assertPushed(SendOutboundWebhookJob::class, 1);
        Queue::assertPushed(
            SendOutboundWebhookJob::class,
            fn (SendOutboundWebhookJob $job): bool => $job->outbound->is($currentOutbound)
                && $job->userId === $currentUser->id,
        );
    }

    public function test_observer_does_not_replace_an_empty_direct_owner_with_the_current_user(): void
    {
        $currentUser = User::factory()->create();
        $this->createOutbound($currentUser, 'test.unowned_source_created');

        $this->actingAs($currentUser);
        Queue::fake();

        $registry = new HookEventRegistry;
        $registry->register(
            key: 'test.unowned_source_created',
            label: 'Test Unowned Source Created',
            modelClass: DirectlyOwnedHookSource::class,
        );

        (new HookModelObserver($registry))->created(new DirectlyOwnedHookSource([
            'user_id' => null,
        ]));

        Queue::assertNothingPushed();
    }

    public function test_outbound_trigger_must_be_registered(): void
    {
        $registry = new HookEventRegistry;
        $registry->register(
            key: 'test.registered',
            label: 'Registered Trigger',
            modelClass: DirectlyOwnedHookSource::class,
        );
        $request = StoreOutboundRequest::create('/hook/outbound', 'POST', [
            'name' => 'Test outbound',
            'provider' => 'generic',
            'target_url' => 'https://8.8.8.8/webhook',
            'trigger' => 'test.unregistered',
        ]);

        $validator = Validator::make($request->all(), $request->rules($registry));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('trigger', $validator->errors()->toArray());
    }

    public function test_managed_provider_config_rejects_unknown_credential_keys(): void
    {
        $registry = new HookEventRegistry;
        $request = StoreOutboundRequest::create('/hook/outbound', 'POST', [
            'name' => 'Test Telegram outbound',
            'provider' => 'telegram',
            'provider_config' => [
                'token' => 'secret-token',
                'chat_id' => '1234',
                'unexpected' => 'not allowed',
            ],
        ]);

        $validator = Validator::make($request->all(), $request->rules($registry));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('provider_config', $validator->errors()->toArray());
    }

    #[DataProvider('invalidManagedProviderUrlProvider')]
    public function test_managed_provider_urls_are_rejected_when_storing_and_updating(string $provider, string $url): void
    {
        $registry = new HookEventRegistry;
        $payload = [
            'name' => 'Managed outbound',
            'provider' => $provider,
            'provider_config' => ['webhook_url' => $url],
        ];
        $requests = [
            StoreOutboundRequest::create('/hook/outbound', 'POST', $payload),
            UpdateOutboundRequest::create('/hook/outbound/example', 'PUT', $payload),
        ];

        foreach ($requests as $request) {
            $validator = Validator::make($request->all(), $request->rules($registry));

            $this->assertTrue($validator->fails());
            $this->assertArrayHasKey('provider_config.webhook_url', $validator->errors()->toArray());
        }
    }

    public function test_dashboard_widgets_only_include_the_authenticated_users_hooks_and_deliveries(): void
    {
        config(['dashboard.cache_ttl' => 0]);

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        HookInbound::withoutEvents(fn (): HookInbound => HookInbound::create([
            'user_id' => $owner->id,
            'name' => 'Owner inbound',
            'slug' => 'ownerhook001',
        ]));
        HookInbound::withoutEvents(fn (): HookInbound => HookInbound::create([
            'user_id' => $otherUser->id,
            'name' => 'Other inbound',
            'slug' => 'otherhook001',
        ]));

        $ownerOutbound = $this->createOutbound($owner, 'test.owner');
        $otherOutbound = $this->createOutbound($otherUser, 'test.other');
        $ownerDelivery = HookOutboundDelivery::create([
            'outbound_id' => $ownerOutbound->id,
            'response_status' => 200,
        ]);
        $otherDelivery = HookOutboundDelivery::create([
            'outbound_id' => $otherOutbound->id,
            'response_status' => 200,
        ]);

        $this->actingAs($owner);

        $widgetRegistry = app(DashboardWidgetRegistry::class);
        app(HookDashboardService::class)->registerWidgets($widgetRegistry, 'Hook');
        $widgets = collect($widgetRegistry->getWidgetsForModule('Hook'));

        $this->assertSame(1, $widgets->firstWhere('title', 'Inbound Webhooks')['value']);
        $this->assertSame(1, $widgets->firstWhere('title', 'Outbound Webhooks')['value']);

        $deliveryIds = array_column($widgets->firstWhere('title', 'Recent Webhook Deliveries')['data'], 'id');
        $this->assertSame([$ownerDelivery->id], $deliveryIds);
        $this->assertNotContains($otherDelivery->id, $deliveryIds);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidManagedProviderUrlProvider(): array
    {
        return [
            'Discord requires HTTPS' => ['discord', 'http://discord.com/api/webhooks/123/token'],
            'Discord rejects lookalike hosts' => ['discord', 'https://discord.com.example.test/api/webhooks/123/token'],
            'Discord requires the webhook path' => ['discord', 'https://discord.com/api/channels/123/token'],
            'Slack requires HTTPS' => ['slack', 'http://hooks.slack.com/services/T123/B456/secret'],
            'Slack rejects lookalike hosts' => ['slack', 'https://hooks.slack.com.example.test/services/T123/B456/secret'],
            'Slack requires the services path' => ['slack', 'https://hooks.slack.com/api/T123/B456/secret'],
        ];
    }

    private function createOutbound(User $user, string $trigger): HookOutbound
    {
        return HookOutbound::withoutEvents(fn (): HookOutbound => HookOutbound::create([
            'user_id' => $user->id,
            'name' => 'Test outbound',
            'target_url' => 'https://8.8.8.8/webhook',
            'method' => 'POST',
            'trigger' => $trigger,
            'provider' => 'generic',
            'is_active' => true,
        ]));
    }
}

class DirectlyOwnedHookSource extends Model
{
    protected $guarded = [];
}

class IndirectlyOwnedHookSource extends Model
{
    protected $guarded = [];
}
