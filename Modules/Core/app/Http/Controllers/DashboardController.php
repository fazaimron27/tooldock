<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Modules\ModuleStatusService;
use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index(
        DashboardWidgetRegistry $widgetRegistry,
        ModuleStatusService $moduleStatusService
    ): Response {
        Gate::authorize('core.dashboard.view');

        $systemHealth = $this->calculateSystemHealth();
        $widgets = $widgetRegistry->getOverviewWidgets();
        $modules = $this->getActiveModules($moduleStatusService);

        return Inertia::render('Dashboard', [
            'systemHealth' => $systemHealth,
            'widgets' => $widgets,
            'modules' => $modules,
        ]);
    }

    /**
     * Calculate system health metrics (module counts).
     *
     * @return array{total: int, active: int, inactive: int}
     */
    private function calculateSystemHealth(): array
    {
        $total = DB::table('modules_statuses')->count();

        $active = DB::table('modules_statuses')
            ->where('is_installed', true)
            ->where('is_active', true)
            ->count();

        $inactive = DB::table('modules_statuses')
            ->where('is_installed', true)
            ->where('is_active', false)
            ->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
        ];
    }

    /**
     * Display the Core module dashboard.
     */
    public function module(DashboardWidgetRegistry $widgetRegistry): Response
    {
        Gate::authorize('core.dashboard.view');

        $widgets = $widgetRegistry->getWidgetsForModule('Core', 'detail');

        return Inertia::render('Modules::Core/Dashboard', [
            'widgets' => $widgets,
        ]);
    }

    /**
     * Get list of active modules with their dashboard routes.
     *
     * @param  ModuleStatusService  $moduleStatusService
     * @return array<int, array{name: string, route: string}>
     */
    private function getActiveModules(ModuleStatusService $moduleStatusService): array
    {
        $statuses = DB::table('modules_statuses')
            ->where('is_installed', true)
            ->where('is_active', true)
            ->select('name')
            ->get();

        return $statuses->map(function ($status) {
            $moduleNameLower = strtolower($status->name);
            $routeName = "{$moduleNameLower}.dashboard";

            try {
                $route = route($routeName);
            } catch (RouteNotFoundException $e) {
                return null;
            }

            return [
                'name' => $status->name,
                'route' => $route,
            ];
        })->filter()->values()->toArray();
    }
}
