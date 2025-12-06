<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Modules\ModuleLifecycleService;
use App\Services\Modules\ModuleRegistryHelper;
use App\Services\Modules\ModuleStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        /**
         * Synchronize module status caches after potential external database changes.
         * Ensures DatabaseActivator and ModuleStatusService caches are aligned.
         */
        $this->registryHelper->reloadStatuses();

        /**
         * Conditionally scan modules only in development or when explicitly configured.
         * Module::all() uses cached scan results, so scanning on every request is unnecessary in production.
         */
        if (app()->environment('local') || config('modules.force_scan', false)) {
            Module::scan();
        }

        $allModules = Module::all();

        $statuses = $this->statusService->getAllStatusesWithVersion();

        $modules = collect($allModules)->map(function ($module) use ($statuses) {
            $moduleName = $module->getName();
            $status = $statuses[$moduleName] ?? null;

            return [
                'name' => $moduleName,
                'description' => $module->get('description', ''),
                'version' => ($status !== null && isset($status['version'])) ? $status['version'] : $module->get('version', '1.0.0'),
                'icon' => $module->get('icon', 'Package'),
                'author' => $module->get('author', ''),
                'is_installed' => ($status !== null && isset($status['is_installed'])) ? $status['is_installed'] : false,
                'is_active' => ($status !== null && isset($status['is_active'])) ? $status['is_active'] : false,
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
            return redirect()->back()->with('error', $this->formatErrorMessage($e->getMessage(), 'install'));
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
            return redirect()->back()->with('error', $this->formatErrorMessage($e->getMessage(), 'uninstall'));
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
            return redirect()->back()->with('error', $this->formatErrorMessage($e->getMessage(), 'toggle'));
        }
    }

    /**
     * Get the route URL for a module's index page.
     * Tries to use Ziggy route name first, falls back to manual URL construction.
     *
     * @param  string  $moduleName  The module name (e.g., "Blog")
     * @return string The route URL (e.g., "/tooldock/blog" or route('blog.index'))
     */
    private function getModuleRouteUrl(string $moduleName): string
    {
        $moduleNameLower = strtolower($moduleName);
        $routeName = "{$moduleNameLower}.index";

        try {
            if (Route::has($routeName)) {
                return route($routeName);
            }
        } catch (\Exception) {
            // Route not found, use fallback URL construction
        }

        /**
         * Fallback: Construct URL manually using standard module route prefix.
         * All module routes follow the /tooldock/{module-name} pattern.
         */
        return "/tooldock/{$moduleNameLower}";
    }

    /**
     * Format error message for display
     *
     * Normalizes error messages by removing newlines and adding operation context
     * if the error doesn't already start with "Cannot ".
     *
     * @param  string  $message  The original error message
     * @param  string  $operation  The operation being performed (install, uninstall, toggle)
     * @return string Formatted error message
     */
    private function formatErrorMessage(string $message, string $operation): string
    {
        $errorMessage = str_replace("\n", ' ', trim($message));

        if (! str_starts_with($errorMessage, 'Cannot ')) {
            $errorMessage = "Failed to {$operation} module: {$errorMessage}";
        }

        return $errorMessage;
    }
}
