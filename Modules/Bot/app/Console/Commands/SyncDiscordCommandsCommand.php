<?php

/**
 * bot:discord-sync
 *
 * Syncs all registered bot commands to Discord's Application Commands API.
 * Must be run after adding new commands or when setting up a new Discord bot.
 *
 * Supports global (all guilds) or guild-specific registration.
 * Guild commands propagate instantly; global commands take up to 1 hour.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Bot\Console\Commands;

use App\Services\Registry\BotCommandRegistryInterface;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Modules\Bot\Enums\BotDriver;
use Modules\Bot\Models\BotPlatform;

class SyncDiscordCommandsCommand extends Command
{
    protected $signature = 'bot:discord-sync
                            {--platform= : Specific BotPlatform ID to sync (defaults to all active Discord platforms)}
                            {--guild=    : Sync to a specific guild ID only (instant propagation)}
                            {--global    : Register commands globally instead of per-guild (up to 1 hour delay)}';

    protected $description = 'Register bot slash commands with the Discord Application Commands API';

    public function __construct(
        private readonly BotCommandRegistryInterface $registry,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $commands = $this->buildCommandPayloads();

        if (empty($commands)) {
            $this->warn('No commands registered in BotCommandRegistry. Nothing to sync.');

            return Command::SUCCESS;
        }

        $this->info('Commands to sync: '.implode(', ', array_column($commands, 'name')));

        $platforms = $this->resolvePlatforms();

        if ($platforms->isEmpty()) {
            $this->error('No active Discord platforms found.');

            return Command::FAILURE;
        }

        $global = $this->option('global');
        $guildId = $this->option('guild');

        foreach ($platforms as $platform) {
            $this->syncPlatform($platform, $commands, $global, $guildId);
        }

        return Command::SUCCESS;
    }

    // -------------------------------------------------------------------------

    /**
     * Build Discord-formatted command payloads from the registered bot commands.
     */
    private function buildCommandPayloads(): array
    {
        return collect($this->registry->all())
            ->map(fn ($cmd, $key) => [
                'name' => $key,
                'description' => $cmd['label'] ?? "/{$key}",
                'options' => [],
            ])
            ->values()
            ->all();
    }

    /**
     * Resolve which BotPlatform(s) to sync.
     */
    private function resolvePlatforms()
    {
        $query = BotPlatform::where('driver', BotDriver::Discord)->where('is_active', true);

        if ($platformId = $this->option('platform')) {
            $query->where('id', $platformId);
        }

        return $query->get();
    }

    /**
     * Push the command list to Discord for a single platform.
     */
    private function syncPlatform(BotPlatform $platform, array $commands, bool $global, ?string $guildId): void
    {
        $appId = $platform->credentials['application_id'] ?? null;
        $token = $platform->credentials['bot_token'] ?? null;

        if (! $appId || ! $token) {
            $this->error("[{$platform->name}] Missing application_id or bot_token in credentials. Skipping.");

            return;
        }

        $this->line('');
        $this->info("→ Syncing platform: {$platform->name} (app: {$appId})");

        if ($global) {
            $this->syncGlobal($appId, $token, $commands);
        } else {
            $targetGuildId = $guildId ?? ($platform->credentials['guild_id'] ?? null);

            if (! $targetGuildId) {
                $this->warn("  No guild_id found in credentials for {$platform->name}. Use --guild=ID or store guild_id in credentials.");

                return;
            }

            $this->syncGuild($appId, $token, $targetGuildId, $commands);
        }
    }

    /**
     * PUT /applications/{id}/commands  (global — up to 1 hour propagation)
     */
    private function syncGlobal(string $appId, string $token, array $commands): void
    {
        $url = "https://discord.com/api/v10/applications/{$appId}/commands";
        /** @var Response $response */
        $response = Http::withToken($token, 'Bot')->put($url, $commands);

        $this->reportResponse($response, 'global');
    }

    /**
     * PUT /applications/{id}/guilds/{guild}/commands  (instant propagation)
     */
    private function syncGuild(string $appId, string $token, string $guildId, array $commands): void
    {
        $url = "https://discord.com/api/v10/applications/{$appId}/guilds/{$guildId}/commands";
        /** @var Response $response */
        $response = Http::withToken($token, 'Bot')->put($url, $commands);

        $this->reportResponse($response, "guild {$guildId}");
    }

    private function reportResponse(Response $response, string $scope): void
    {
        if ($response->successful()) {
            $count = count($response->json() ?? []);
            $this->info("  ✓ {$count} command(s) registered to {$scope}.");
        } else {
            $this->error("  ✗ Discord API error ({$response->status()}) for {$scope}: ".$response->body());
        }
    }
}
