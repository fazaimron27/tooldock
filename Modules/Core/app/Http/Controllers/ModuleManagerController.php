<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Modules\ModuleLifecycleService;
use App\Services\Modules\ModuleRegistryHelper;
use App\Services\Modules\ModuleStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Nwidart\Modules\Facades\Module;

class ModuleManagerController extends Controller
{
    public function __construct(
        private ModuleLifecycleService $lifecycleService,
        private ModuleStatusService $statusService,
        private ModuleRegistryHelper $registryHelper
    ) {}

    /**
     * Display a listing of all modules.
     */
    public function index(): Response
    {
        Gate::authorize('core.modules.manage');

        $this->registryHelper->reloadStatuses();

        Module::scan();
        $allModules = Module::all();

        $statuses = DB::table('modules_statuses')
            ->select('name', 'is_installed', 'is_active', 'version')
            ->get()
            ->keyBy('name');

        $modules = collect($allModules)->map(function ($module) use ($statuses) {
            $moduleName = $module->getName();
            $status = $statuses->get($moduleName);

            return [
                'name' => $moduleName,
                'description' => $module->get('description', ''),
                'version' => $module->get('version', '1.0.0'),
                'icon' => $module->get('icon', 'Package'),
                'author' => $module->get('author', ''),
                'is_installed' => $status ? (bool) $status->is_installed : false,
                'is_active' => $status ? (bool) $status->is_active : false,
                'requires' => $module->get('requires', []),
                'protected' => $module->get('protected', false),
                'keywords' => $module->get('keywords', []),
                'priority' => $module->get('priority', 0),
            ];
        })
            ->sortBy(function ($module) {
                if ($module['protected']) {
                    return ['protected' => 0, 'priority' => $module['priority'], 'name' => $module['name']];
                }

                return ['protected' => 1, 'name' => $module['name']];
            })
            ->values();

        return Inertia::render('Modules::Core/Modules/Index', [
            'modules' => $modules,
        ]);
    }

    /**
     * Install a module.
     */
    public function install(Request $request): RedirectResponse
    {
        Gate::authorize('core.modules.manage');

        $request->validate([
            'module' => ['required', 'string'],
        ]);

        try {
            $this->lifecycleService->install($request->module);

            return redirect()
                ->back()
                ->with('success', "Module '{$request->module}' installed successfully.")
                ->with('module_route_url', $this->getModuleRouteUrl($request->module));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', "Failed to install module: {$e->getMessage()}");
        }
    }

    /**
     * Uninstall a module.
     */
    public function uninstall(Request $request): RedirectResponse
    {
        Gate::authorize('core.modules.manage');

        $request->validate([
            'module' => ['required', 'string'],
        ]);

        try {
            $this->lifecycleService->uninstall($request->module);

            return redirect()->back()->with('success', "Module '{$request->module}' uninstalled successfully.");
        } catch (\Exception $e) {
            $errorMessage = str_replace("\n", ' ', trim($e->getMessage()));

            if (! str_starts_with($errorMessage, 'Cannot ')) {
                $errorMessage = "Failed to uninstall module: {$errorMessage}";
            }

            return redirect()->back()->with('error', $errorMessage);
        }
    }

    /**
     * Toggle module enable/disable status.
     */
    public function toggle(Request $request): RedirectResponse
    {
        Gate::authorize('core.modules.manage');

        $request->validate([
            'module' => ['required', 'string'],
            'action' => ['required', 'string', 'in:enable,disable'],
        ]);

        try {
            if ($request->action === 'enable') {
                $this->lifecycleService->enable($request->module);
                $message = "Module '{$request->module}' enabled successfully.";

                return redirect()
                    ->back()
                    ->with('success', $message)
                    ->with('module_route_url', $this->getModuleRouteUrl($request->module));
            } else {
                $this->lifecycleService->disable($request->module);
                $message = "Module '{$request->module}' disabled successfully.";

                return redirect()->back()->with('success', $message);
            }
        } catch (\Exception $e) {
            $errorMessage = str_replace("\n", ' ', trim($e->getMessage()));

            if (! str_starts_with($errorMessage, 'Cannot ')) {
                $errorMessage = "Failed to toggle module: {$errorMessage}";
            }

            return redirect()->back()->with('error', $errorMessage);
        }
    }

    /**
     * Get the route URL for a module's index page.
     * Tries to use Ziggy route name first, falls back to manual URL construction.
     *
     * @param  string  $moduleName  The module name (e.g., "Blog")
     * @return string The route URL (e.g., "/blog" or route('blog.index'))
     */
    private function getModuleRouteUrl(string $moduleName): string
    {
        $moduleNameLower = strtolower($moduleName);
        $routeName = "{$moduleNameLower}.index";

        try {
            if (Route::has($routeName)) {
                return route($routeName);
            }
        } catch (\Exception $e) {
            // Route doesn't exist, fall through to fallback
        }

        return "/{$moduleNameLower}";
    }
}
