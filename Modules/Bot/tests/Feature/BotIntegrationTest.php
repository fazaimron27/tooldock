<?php

namespace Modules\Bot\Tests\Feature;

use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Modules\Bot\Drivers\DiscordDriver;
use Modules\Bot\Drivers\TelegramDriver;
use Modules\Bot\Enums\BotDriver;
use Modules\Bot\Enums\BotMessageDirection;
use Modules\Bot\Enums\BotMessageStatus;
use Modules\Bot\Handlers\InboundWebhookHandler;
use Modules\Bot\Handlers\Platforms\DiscordInboundPlatform;
use Modules\Bot\Handlers\Platforms\TelegramInboundPlatform;
use Modules\Bot\Http\Controllers\BotConnectController;
use Modules\Bot\Http\Controllers\BotPlatformController;
use Modules\Bot\Models\BotConnection;
use Modules\Bot\Models\BotMessage;
use Modules\Bot\Models\BotPlatform;
use Modules\Bot\Observers\BotHookInboundObserver;
use Modules\Bot\Processors\TelegramInboundProcessor;
use Modules\Bot\Services\BotCommandRegistry;
use Modules\Bot\Services\BotDashboardService;
use Modules\Bot\Services\BotDriverFactory;
use Modules\Bot\Services\BotManager;
use Modules\Core\Models\User;
use Modules\Hook\Models\HookInbound;
use Modules\Hook\Models\HookInboundRequest;
use RuntimeException;
use Tests\TestCase;

class BotIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateBotTables();
        $this->registerBotRoutes();

        Gate::before(fn (): bool => true);
        HookInbound::observe(BotHookInboundObserver::class);
    }

    public function test_signed_link_submission_uses_signed_query_values_instead_of_request_body(): void
    {
        $user = User::factory()->create();
        [$platform] = $this->createTelegramPlatform($user);
        [$otherPlatform] = $this->createTelegramPlatform($user, 'Other bot');

        $url = URL::temporarySignedRoute('bot.connect', now()->addMinutes(10), [
            'bot_platform_id' => $platform->id,
            'platform_user_id' => 'telegram-123',
            'platform_username' => 'signed-user',
        ], absolute: false);

        $this->actingAs($user)->post($url, [
            'bot_platform_id' => $otherPlatform->id,
            'platform_user_id' => 'attacker-id',
            'platform_username' => 'attacker',
        ])->assertRedirect(route('bot.index'));

        $connection = BotConnection::sole();

        $this->assertSame($platform->id, $connection->bot_platform_id);
        $this->assertSame('telegram-123', $connection->platform_user_id);
        $this->assertSame('signed-user', $connection->platform_username);
    }

    public function test_tampered_link_signature_is_rejected_on_submission(): void
    {
        $user = User::factory()->create();
        [$platform] = $this->createTelegramPlatform($user);

        $url = URL::temporarySignedRoute('bot.connect', now()->addMinutes(10), [
            'bot_platform_id' => $platform->id,
            'platform_user_id' => 'telegram-123',
            'platform_username' => 'signed-user',
        ], absolute: false);
        $tamperedUrl = str_replace('platform_user_id=telegram-123', 'platform_user_id=attacker', $url);

        $this->actingAs($user)->post($tamperedUrl)->assertForbidden();

        $this->assertSame(0, BotConnection::count());
    }

    public function test_platform_and_hook_creation_roll_back_together(): void
    {
        $user = User::factory()->create();

        BotPlatform::creating(function (): never {
            throw new RuntimeException('Platform creation failed.');
        });

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($user)->post(route('bot.platform.store'), [
                'driver' => BotDriver::Telegram->value,
                'name' => 'Transactional bot',
                'credentials' => ['bot_token' => 'token'],
                'is_active' => true,
            ]);

            $this->fail('Expected platform creation to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Platform creation failed.', $exception->getMessage());
        }

        $this->assertSame(0, BotPlatform::withTrashed()->count());
        $this->assertSame(0, HookInbound::withTrashed()->count());
    }

    public function test_discord_webhook_url_must_use_the_exact_discord_endpoint(): void
    {
        $user = User::factory()->create();
        $invalidWebhookUrl = 'https://discord.com.attacker.test/api/webhooks/123/token';

        $this->actingAs($user)->post(route('bot.platform.store'), [
            'driver' => BotDriver::Discord->value,
            'name' => 'Discord bot',
            'credentials' => ['webhook_url' => $invalidWebhookUrl],
            'is_active' => true,
        ])->assertSessionHasErrors('credentials.webhook_url');

        $platform = BotPlatform::create([
            'user_id' => $user->id,
            'driver' => BotDriver::Discord,
            'name' => 'Existing Discord bot',
            'credentials' => ['webhook_url' => 'https://discord.com/api/webhooks/123/token'],
            'is_active' => true,
        ]);

        $this->actingAs($user)->put(route('bot.platform.update', $platform), [
            'driver' => BotDriver::Telegram->value,
            'credentials' => ['webhook_url' => $invalidWebhookUrl],
        ])->assertSessionHasErrors('credentials.webhook_url');

        $platform->update(['credentials' => ['webhook_url' => $invalidWebhookUrl]]);
        Http::preventStrayRequests();

        $this->assertFalse((new DiscordDriver)->testConnection($platform)['ok']);
        Http::assertNothingSent();
    }

    public function test_dashboard_widgets_only_include_the_authenticated_owners_platforms(): void
    {
        config(['dashboard.cache_ttl' => 0]);

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        [$ownerPlatform] = $this->createTelegramPlatform($owner, 'Owner bot');
        [$otherPlatform] = $this->createTelegramPlatform($otherUser, 'Other bot');

        $ownerMessage = BotMessage::create([
            'user_id' => $owner->id,
            'bot_platform_id' => $ownerPlatform->id,
            'direction' => BotMessageDirection::Outbound,
            'status' => BotMessageStatus::Delivered,
        ]);
        BotMessage::create([
            'user_id' => $otherUser->id,
            'bot_platform_id' => $otherPlatform->id,
            'direction' => BotMessageDirection::Outbound,
            'status' => BotMessageStatus::Failed,
        ]);

        $this->actingAs($owner);

        $registry = app(DashboardWidgetRegistry::class);
        app(BotDashboardService::class)->registerWidgets($registry, 'BotOwnershipTest');
        $widgets = collect($registry->getWidgetsForModule('BotOwnershipTest'))->keyBy('title');

        $this->assertSame(1, $widgets['Bot Integrations']['value']);
        $this->assertSame(1, $widgets['Messages Sent']['value']);
        $this->assertSame([$ownerMessage->id], array_column($widgets['Recent Bot Messages']['data'], 'id'));
    }

    public function test_deleting_platform_does_not_recreate_or_orphan_hook_endpoint(): void
    {
        Http::fake();

        $user = User::factory()->create();
        [$platform, $inbound] = $this->createTelegramPlatform($user);

        $this->actingAs($user)
            ->delete(route('bot.platform.destroy', $platform))
            ->assertRedirect();

        $this->assertSoftDeleted($platform);
        $this->assertSoftDeleted($inbound);
        $this->assertSame(1, HookInbound::withTrashed()->count());
        $this->assertSame(0, HookInbound::count());
    }

    public function test_telegram_webhook_secret_is_encrypted_registered_and_verified(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true]),
        ]);

        $user = User::factory()->create();
        [$platform, $inbound] = $this->createTelegramPlatform($user, credentials: [
            'bot_token' => 'telegram-token',
        ]);

        (new TelegramDriver)->registerWebhook($platform, 'https://example.test/webhook');

        $platform->refresh();
        $secret = $platform->credentials['webhook_secret_token'];

        $this->assertSame(64, strlen($secret));
        $this->assertStringNotContainsString($secret, $platform->getRawOriginal('credentials'));

        Http::assertSent(fn ($request): bool => $request['secret_token'] === $secret);

        $processor = new TelegramInboundProcessor;
        $validRequest = Request::create('/webhook', 'POST', server: [
            'HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN' => $secret,
        ]);

        $this->assertNull($processor->verify($validRequest, $inbound));
        $this->assertNull($validRequest->header('X-Telegram-Bot-Api-Secret-Token'));

        $invalidRequest = Request::create('/webhook', 'POST', server: [
            'HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN' => 'wrong-secret',
        ]);

        $this->assertSame(401, $processor->verify($invalidRequest, $inbound)?->getStatusCode());
    }

    public function test_bot_connection_migration_and_model_uuid_work_on_sqlite(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertTrue(Schema::hasTable('bot_connections'));

        $user = User::factory()->create();
        [$platform] = $this->createTelegramPlatform($user);

        $connection = BotConnection::create([
            'bot_platform_id' => $platform->id,
            'platform_user_id' => 'telegram-123',
            'platform_username' => 'sqlite-user',
            'user_id' => $user->id,
        ]);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $connection->id,
        );
        $this->assertModelExists($connection);
    }

    public function test_inbound_bot_messages_are_logged(): void
    {
        Http::fake();

        $user = User::factory()->create();
        [$platform, $inbound] = $this->createTelegramPlatform($user);
        $payload = [
            'message' => [
                'text' => '/unknown',
                'from' => ['id' => 123, 'username' => 'telegram-user'],
                'chat' => ['id' => 123],
            ],
        ];
        $inboundRequest = HookInboundRequest::create([
            'inbound_id' => $inbound->id,
            'method' => 'POST',
            'url' => 'https://example.test/webhook',
            'payload' => $payload,
            'source_ip' => '127.0.0.1',
        ]);

        $factory = new BotDriverFactory;
        $handler = new InboundWebhookHandler(
            new BotCommandRegistry,
            new BotManager($factory),
            new TelegramInboundPlatform,
            new DiscordInboundPlatform,
        );

        $handler->handle(['request' => $inboundRequest]);

        $message = BotMessage::sole();

        $this->assertSame($platform->id, $message->bot_platform_id);
        $this->assertSame(BotMessageDirection::Inbound, $message->direction);
        $this->assertSame('unknown', $message->command_key);
        $this->assertSame($payload, $message->raw_payload);
    }

    private function registerBotRoutes(): void
    {
        Route::middleware(['auth', 'verified', SubstituteBindings::class])->group(function (): void {
            Route::get('/bot', fn () => response('Bot'))->name('bot.index');
            Route::get('/bot/connect', [BotConnectController::class, 'show'])->name('bot.connect');
            Route::post('/bot/connect', [BotConnectController::class, 'store'])->name('bot.connect.store');
            Route::post('/bot/platform', [BotPlatformController::class, 'store'])->name('bot.platform.store');
            Route::put('/bot/platform/{botPlatform}', [BotPlatformController::class, 'update'])
                ->name('bot.platform.update');
            Route::delete('/bot/platform/{botPlatform}', [BotPlatformController::class, 'destroy'])
                ->name('bot.platform.destroy');
        });

        Route::getRoutes()->refreshNameLookups();
    }

    private function migrateBotTables(): void
    {
        $hookMigrationFiles = [
            'Modules/Hook/database/migrations/2026_02_25_003218_create_hook_inbounds_table.php',
            'Modules/Hook/database/migrations/2026_02_25_003219_create_hook_inbound_requests_table.php',
            'Modules/Hook/database/migrations/2026_02_25_003220_create_hook_outbounds_table.php',
            'Modules/Hook/database/migrations/2026_02_25_003221_create_hook_outbound_deliveries_table.php',
        ];
        $botMigrationFiles = [
            'Modules/Bot/database/migrations/2026_03_01_012207_create_bot_platforms_table.php',
            'Modules/Bot/database/migrations/2026_03_01_012208_create_bot_messages_table.php',
            'Modules/Bot/database/migrations/2026_03_01_012209_create_bot_connections_table.php',
        ];

        if (! Schema::hasTable('hook_inbounds')) {
            foreach ($hookMigrationFiles as $migrationFile) {
                $migration = require base_path($migrationFile);
                $migration->up();
            }
        }

        if (! Schema::hasTable('bot_platforms')) {
            foreach ($botMigrationFiles as $migrationFile) {
                $migration = require base_path($migrationFile);
                $migration->up();
            }
        }
    }

    /**
     * @param  array<string, string>  $credentials
     * @return array{BotPlatform, HookInbound}
     */
    private function createTelegramPlatform(
        User $user,
        string $name = 'Telegram bot',
        array $credentials = ['bot_token' => 'token', 'webhook_secret_token' => 'secret'],
    ): array {
        $inbound = HookInbound::create([
            'user_id' => $user->id,
            'name' => 'Bot: '.$name,
            'is_active' => true,
        ]);

        $platform = BotPlatform::create([
            'user_id' => $user->id,
            'driver' => BotDriver::Telegram,
            'name' => $name,
            'credentials' => $credentials,
            'is_active' => true,
            'hook_inbound_slug' => $inbound->slug,
        ]);

        return [$platform, $inbound];
    }
}
