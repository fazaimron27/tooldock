<?php

namespace App\Console\Commands;

use App\Exceptions\MissingDependencyException;
use App\Services\Modules\ModuleLifecycleService;
use Illuminate\Console\Command;

class ModuleManageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:manage {module} {--action= : install, uninstall, enable, disable} {--seed : Run seeders during installation (for development)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage module lifecycle (install, uninstall, enable, disable)';

    public function __construct(
        private ModuleLifecycleService $lifecycleService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $moduleName = $this->argument('module');
        $action = $this->option('action');

        if (empty($action)) {
            $this->error('Action is required. Use --action=install|uninstall|enable|disable');

            return Command::FAILURE;
        }

        $validActions = ['install', 'uninstall', 'enable', 'disable'];

        if (! in_array($action, $validActions, true)) {
            $this->error("Invalid action '{$action}'. Valid actions are: ".implode(', ', $validActions));

            return Command::FAILURE;
        }

        try {
            match ($action) {
                'install' => $this->handleInstall($moduleName),
                'uninstall' => $this->handleUninstall($moduleName),
                'enable' => $this->handleEnable($moduleName),
                'disable' => $this->handleDisable($moduleName),
            };

            return Command::SUCCESS;
        } catch (MissingDependencyException $e) {
            $this->error($e->getMessage());

            return Command::FAILURE;
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->error("An error occurred: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }

    private function handleInstall(string $moduleName): void
    {
        $withSeed = $this->option('seed');

        $this->info("Installing module: {$moduleName}...");

        $this->lifecycleService->install($moduleName, $withSeed);

        if ($withSeed) {
            $this->info("Module '{$moduleName}' installed successfully with demo data!");
        } else {
            $this->info("Module '{$moduleName}' installed successfully!");
        }
    }

    private function handleUninstall(string $moduleName): void
    {
        $this->info("Uninstalling module: {$moduleName}...");
        $this->lifecycleService->uninstall($moduleName);
        $this->info("Module '{$moduleName}' uninstalled successfully!");
    }

    private function handleEnable(string $moduleName): void
    {
        $this->info("Enabling module: {$moduleName}...");
        $this->lifecycleService->enable($moduleName);
        $this->info("Module '{$moduleName}' enabled successfully!");
    }

    private function handleDisable(string $moduleName): void
    {
        $this->info("Disabling module: {$moduleName}...");
        $this->lifecycleService->disable($moduleName);
        $this->info("Module '{$moduleName}' disabled successfully!");
    }
}
