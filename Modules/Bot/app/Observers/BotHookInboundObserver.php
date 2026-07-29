<?php

/**
 * BotHookInboundObserver
 *
 * Listens for HookInbound deletions. If the deleted inbound was powering
 * a BotPlatform, it recreates the inbound endpoint and re-registers the
 * Telegram / Discord webhook automatically, so the bot self-heals.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Bot\Observers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Bot\Models\BotPlatform;
use Modules\Bot\Services\BotDriverFactory;
use Modules\Hook\Models\HookInbound;

class BotHookInboundObserver
{
    public function __construct(private readonly BotDriverFactory $factory) {}

    /**
     * Fires after a HookInbound is soft-deleted or force-deleted.
     * Recreates the endpoint for any BotPlatform that depended on it.
     */
    public function deleted(HookInbound $inbound): void
    {
        $platforms = BotPlatform::where('hook_inbound_slug', $inbound->slug)
            ->where('is_active', true)
            ->get();

        if ($platforms->isEmpty()) {
            return;
        }

        foreach ($platforms as $platform) {
            try {
                $newInbound = DB::transaction(function () use ($platform): HookInbound {
                    $inbound = HookInbound::create([
                        'user_id' => $platform->user_id,
                        'name' => 'Bot: '.$platform->name,
                        'description' => 'Auto-recreated by Bot module after inbound deletion.',
                        'is_active' => true,
                    ]);

                    $platform->update(['hook_inbound_slug' => $inbound->slug]);

                    return $inbound;
                });

                $webhookUrl = rtrim(config('app.url'), '/').'/api/v1/hook/inbound/'.$newInbound->slug;
                $driver = $this->factory->make($platform->driver->value);
                $driver->registerWebhook($platform, $webhookUrl);

                Log::info('BotHookInboundObserver: recreated inbound after deletion', [
                    'platform' => $platform->id,
                    'old_slug' => $inbound->slug,
                    'new_slug' => $newInbound->slug,
                ]);
            } catch (\Exception $e) {
                Log::error('BotHookInboundObserver: failed to recreate inbound', [
                    'platform' => $platform->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Also fires on restore (soft-delete reversed) — re-register the webhook.
     */
    public function restored(HookInbound $inbound): void
    {
        $platform = BotPlatform::where('hook_inbound_slug', $inbound->slug)
            ->where('is_active', true)
            ->first();

        if (! $platform) {
            return;
        }

        $webhookUrl = rtrim(config('app.url'), '/').'/api/v1/hook/inbound/'.$inbound->slug;
        $driver = $this->factory->make($platform->driver->value);
        $driver->registerWebhook($platform, $webhookUrl);
    }
}
