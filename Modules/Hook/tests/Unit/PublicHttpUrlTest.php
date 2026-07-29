<?php

namespace Modules\Hook\Tests\Unit;

use App\Services\Registry\SignalHandlerRegistry;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Mockery;
use Modules\Hook\Enums\HookOutboundProvider;
use Modules\Hook\Jobs\SendOutboundWebhookJob;
use Modules\Hook\Models\HookOutbound;
use Modules\Hook\Rules\PublicHttpUrl;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicHttpUrlTest extends TestCase
{
    #[DataProvider('unsafeUrlProvider')]
    public function test_it_rejects_unsafe_generic_urls(string $url): void
    {
        $rule = new PublicHttpUrl(fn (string $host): array => match ($host) {
            'private.example' => ['10.0.0.10'],
            'mixed.example' => ['93.184.216.34', '169.254.169.254'],
            default => ['93.184.216.34'],
        });

        $this->assertFalse($rule->isAllowed($url));
    }

    public function test_it_allows_http_urls_that_only_resolve_to_public_addresses(): void
    {
        $rule = new PublicHttpUrl(fn (string $host): array => ['93.184.216.34', '2606:2800:220:1:248:1893:25c8:1946']);

        $this->assertTrue($rule->isAllowed('https://public.example/webhook'));
    }

    public function test_job_revalidates_generic_url_before_dispatch(): void
    {
        Http::fake();

        $outbound = new HookOutbound([
            'name' => 'Rebinding target',
            'target_url' => 'https://rebind.example/webhook',
            'method' => 'POST',
            'provider' => 'generic',
            'is_active' => true,
        ]);
        $job = new SendOutboundWebhookJob($outbound, 'test-user');
        $rule = new PublicHttpUrl(fn (string $host): array => ['127.0.0.1']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Generic outbound webhook URL is not a public HTTP or HTTPS address.');

        try {
            $job->handle(Mockery::mock(SignalHandlerRegistry::class), $rule);
        } finally {
            Http::assertNothingSent();
        }
    }

    #[DataProvider('invalidManagedProviderConfigProvider')]
    public function test_job_revalidates_managed_provider_urls_before_dispatch(string $provider, array $config): void
    {
        Http::fake();

        $outbound = new HookOutbound([
            'name' => 'Managed target',
            'method' => 'POST',
            'provider' => $provider,
            'provider_config' => $config,
            'is_active' => true,
        ]);
        $job = new SendOutboundWebhookJob($outbound, 'test-user');
        $rule = new PublicHttpUrl(fn (string $host): array => ['93.184.216.34']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Outbound webhook URL is invalid for');

        try {
            $job->handle(Mockery::mock(SignalHandlerRegistry::class), $rule);
        } finally {
            Http::assertNothingSent();
        }
    }

    #[DataProvider('validManagedProviderUrlProvider')]
    public function test_managed_provider_url_rules_accept_exact_provider_endpoints(
        HookOutboundProvider $provider,
        string $url,
    ): void {
        $this->assertTrue($provider->isValidUrl($url));
    }

    #[DataProvider('outboundProviderConfigProvider')]
    public function test_job_disables_redirects_for_every_provider(
        string $provider,
        ?string $targetUrl,
        array $config,
    ): void {
        $pendingRequest = Mockery::mock(PendingRequest::class);
        $pendingRequest->shouldReceive('connectTimeout')->once()->with(5)->andReturnSelf();
        $pendingRequest->shouldReceive('withHeaders')->once()->with(Mockery::type('array'))->andReturnSelf();
        $pendingRequest->shouldReceive('withoutRedirecting')->once()->andThrow(
            new \RuntimeException('Redirects disabled.'),
        );
        Http::shouldReceive('timeout')->once()->with(15)->andReturn($pendingRequest);

        $outbound = new HookOutbound([
            'name' => 'Redirect-safe target',
            'target_url' => $targetUrl,
            'method' => 'POST',
            'provider' => $provider,
            'provider_config' => $config,
            'is_active' => true,
        ]);
        $job = new SendOutboundWebhookJob($outbound, 'test-user');
        $rule = new PublicHttpUrl(fn (string $host): array => ['93.184.216.34']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Redirects disabled.');

        $job->handle(Mockery::mock(SignalHandlerRegistry::class), $rule);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unsafeUrlProvider(): array
    {
        return [
            'non-http scheme' => ['ftp://public.example/file'],
            'localhost' => ['http://localhost/hook'],
            'localhost subdomain' => ['http://api.localhost/hook'],
            'loopback IPv4' => ['http://127.0.0.1/hook'],
            'non-canonical IPv4' => ['http://2130706433/hook'],
            'private IPv4' => ['http://10.0.0.1/hook'],
            'link-local IPv4' => ['http://169.254.169.254/latest/meta-data'],
            'shared IPv4' => ['http://100.64.0.1/hook'],
            'multicast IPv4' => ['http://224.0.0.1/hook'],
            'reserved IPv4' => ['http://240.0.0.1/hook'],
            'deprecated relay IPv4' => ['http://192.88.99.1/hook'],
            'loopback IPv6' => ['http://[::1]/hook'],
            'private IPv6' => ['http://[fc00::1]/hook'],
            'link-local IPv6' => ['http://[fe80::1]/hook'],
            'multicast IPv6' => ['http://[ff02::1]/hook'],
            'reserved IPv6' => ['http://[2001:db8::1]/hook'],
            'translation IPv6' => ['http://[64:ff9b::1]/hook'],
            'documentation IPv6' => ['http://[3fff::1]/hook'],
            'DNS private address' => ['https://private.example/hook'],
            'DNS mixed addresses' => ['https://mixed.example/hook'],
        ];
    }

    /**
     * @return array<string, array{string, array<string, string>}>
     */
    public static function invalidManagedProviderConfigProvider(): array
    {
        return [
            'Discord host' => ['discord', ['webhook_url' => 'https://discord.com.example.test/api/webhooks/123/token']],
            'Slack path' => ['slack', ['webhook_url' => 'https://hooks.slack.com/api/T123/B456/secret']],
            'Telegram generated path' => ['telegram', ['token' => '123:token/../escape', 'chat_id' => '123']],
        ];
    }

    /**
     * @return array<string, array{HookOutboundProvider, string}>
     */
    public static function validManagedProviderUrlProvider(): array
    {
        return [
            'Discord' => [HookOutboundProvider::Discord, 'https://discord.com/api/webhooks/123456/Abc_def-token.123'],
            'Slack' => [HookOutboundProvider::Slack, 'https://hooks.slack.com/services/T123ABC/B456DEF/Secret789'],
            'Telegram' => [HookOutboundProvider::Telegram, 'https://api.telegram.org/bot123456:ABC_def-token/sendMessage'],
        ];
    }

    /**
     * @return array<string, array{string, string|null, array<string, string>}>
     */
    public static function outboundProviderConfigProvider(): array
    {
        return [
            'Generic' => ['generic', 'https://public.example/webhook', []],
            'Discord' => ['discord', null, ['webhook_url' => 'https://discord.com/api/webhooks/123456/token']],
            'Slack' => ['slack', null, ['webhook_url' => 'https://hooks.slack.com/services/T123/B456/Secret789']],
            'Telegram' => ['telegram', null, ['token' => '123456:ABC_def-token', 'chat_id' => '123']],
        ];
    }
}
