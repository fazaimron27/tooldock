<?php

/**
 * Bot Controller
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Bot\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Bot\Enums\BotDriver;
use Modules\Bot\Models\BotPlatform;

class BotController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('bot.bridge.view');

        $inboundBaseUrl = rtrim(config('app.url'), '/').'/api/v1/hook/inbound';

        $platforms = BotPlatform::forUser($request->user())
            ->with(['connections.user'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (BotPlatform $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'driver' => $p->driver->value,
                'driver_label' => $p->driver->label(),
                'driver_icon' => $p->driver->icon(),
                'is_active' => $p->is_active,
                'tested_at' => $p->tested_at?->toDateTimeString(),
                'created_at' => $p->created_at->toDateTimeString(),
                'hook_inbound_slug' => $p->hook_inbound_slug,
                'webhook_url' => $p->hook_inbound_slug
                    ? $inboundBaseUrl.'/'.$p->hook_inbound_slug
                    : null,
                'connections' => $p->connections->map(fn ($c) => [
                    'platform_username' => $c->platform_username,
                    'user_name' => $c->user?->name,
                    'user_email' => $c->user?->email,
                ]),
            ]);

        return Inertia::render('Modules::Bot/Index', [
            'platforms' => $platforms,
            'driverOptions' => BotDriver::toOptions(),
        ]);
    }

    public function dashboard(DashboardWidgetRegistry $widgetRegistry): Response
    {
        Gate::authorize('bot.dashboard.view');

        $widgets = $widgetRegistry->getWidgetsForModule('Bot', 'detail');
        $moduleMetadata = $widgetRegistry->getAllModuleMetadata();

        return Inertia::render('Modules::Bot/Dashboard', [
            'widgets' => $widgets,
            'moduleMetadata' => $moduleMetadata,
        ]);
    }
}
