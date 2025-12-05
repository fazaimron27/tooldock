<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\App\Constants\Roles;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index(
        DashboardWidgetRegistry $widgetRegistry
    ): Response|RedirectResponse {
        $user = request()->user();

        if ($user && $this->isGuestOnly($user)) {
            return redirect()->route('guest.welcome');
        }

        $systemHealth = $this->calculateSystemHealth();
        $widgets = $widgetRegistry->getOverviewWidgets();
        $modules = $this->getActiveModules();

        return Inertia::render('Dashboard', [
            'systemHealth' => $systemHealth,
            'widgets' => $widgets,
            'modules' => $modules,
        ]);
    }

    /**
     * Check if user is Guest-only (only has Guest group and no permissions).
     *
     * A user is considered Guest-only if:
     * - They are not Super Admin
     * - They have no permissions (direct or through roles, using Spatie's getAllPermissions)
     * - AND they have no permissions through groups
     * - AND they only have Guest group (or no groups)
     *
     * @param  \Modules\Core\App\Models\User  $user
     * @return bool
     */
    private function isGuestOnly($user): bool
    {
        if ($user->hasRole(Roles::SUPER_ADMIN)) {
            return false;
        }

        $allPermissions = $user->getAllPermissions();
        if ($allPermissions->isNotEmpty()) {
            return false;
        }

        if (method_exists($user, 'getGroupPermissions')) {
            $groupPermissions = $user->getGroupPermissions();
            if (! empty($groupPermissions)) {
                return false;
            }
        }

        $hasNonGuestGroup = $user->groups()
            ->where('slug', '!=', 'guest')
            ->exists();

        if ($hasNonGuestGroup) {
            return false;
        }

        return true;
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
     * @return array<int, array{name: string, route: string}>
     */
    private function getActiveModules(): array
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
