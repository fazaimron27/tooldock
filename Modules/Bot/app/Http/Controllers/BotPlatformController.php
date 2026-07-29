<?php

/**
 * Bot Platform Controller
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Bot\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Bot\Http\Requests\StoreBotPlatformRequest;
use Modules\Bot\Http\Requests\UpdateBotPlatformRequest;
use Modules\Bot\Models\BotPlatform;
use Modules\Bot\Services\BotDriverFactory;
use Modules\Bot\Services\BotManager;
use Modules\Hook\Models\HookInbound;

class BotPlatformController extends Controller
{
    public function __construct(
        private readonly BotManager $manager,
        private readonly BotDriverFactory $factory,
    ) {}

    public function store(StoreBotPlatformRequest $request): RedirectResponse
    {
        $platform = DB::transaction(function () use ($request): BotPlatform {
            $inbound = HookInbound::create([
                'user_id' => $request->user()->id,
                'name' => 'Bot: '.$request->input('name'),
                'description' => 'Auto-created by Bot module for inbound command handling.',
                'is_active' => true,
            ]);

            return BotPlatform::create([
                'user_id' => $request->user()->id,
                'driver' => $request->input('driver'),
                'name' => $request->input('name'),
                'credentials' => $request->input('credentials', []),
                'is_active' => $request->boolean('is_active', true),
                'hook_inbound_slug' => $inbound->slug,
            ]);
        });

        $this->registerWebhook($platform, $platform->hook_inbound_slug);

        return back();
    }

    public function update(UpdateBotPlatformRequest $request, BotPlatform $botPlatform): RedirectResponse
    {
        $data = $request->only(['name', 'is_active']);

        if ($request->filled('credentials')) {
            $existing = $botPlatform->credentials ?? [];
            $data['credentials'] = array_merge($existing, $request->input('credentials'));
        }

        $botPlatform->update($data);

        // Re-register in case the token changed
        if ($request->filled('credentials') && $botPlatform->hook_inbound_slug) {
            $botPlatform->refresh();
            $this->registerWebhook($botPlatform, $botPlatform->hook_inbound_slug);
        }

        return back();
    }

    public function destroy(Request $request, BotPlatform $botPlatform): RedirectResponse
    {
        $this->authorize('delete', $botPlatform);

        DB::transaction(function () use ($botPlatform): void {
            $hookInboundSlug = $botPlatform->hook_inbound_slug;
            $hookInbound = $hookInboundSlug
                ? HookInbound::where('slug', $hookInboundSlug)->first()
                : null;

            $botPlatform->updateQuietly([
                'is_active' => false,
                'hook_inbound_slug' => null,
            ]);

            $hookInbound?->delete();

            $botPlatform->delete();
        });

        return back();
    }

    public function test(Request $request, BotPlatform $botPlatform): JsonResponse
    {
        $this->authorize('test', $botPlatform);

        $result = $this->manager->test($botPlatform);

        return response()->json($result);
    }

    /**
     * Register the Hook inbound URL as the bot platform's webhook.
     * Called automatically on store() and update() so the user never has to curl manually.
     */
    private function registerWebhook(BotPlatform $platform, string $slug): void
    {
        $webhookUrl = rtrim(config('app.url'), '/').'/api/v1/hook/inbound/'.$slug;
        $driver = $this->factory->make($platform->driver->value);
        $driver->registerWebhook($platform, $webhookUrl);
    }
}
