<?php

namespace App\Console\Commands;

use App\Exceptions\MissingDependencyException;
use App\Services\Modules\ModuleLifecycleService;
use Illuminate\Console\Command;

/**
 * Artisan command to manage module lifecycle operations
 *
 * Provides CLI interface for installing, uninstalling, enabling, and disabling modules.
 * Handles dependency validation and provides user-friendly error messages.
 */
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
     * Execute the console command
     *
     * Validates action parameter and delegates to appropriate handler method.
     * Catches and displays user-friendly error messages for common exceptions.
     *
     * @return int Command exit code (SUCCESS or FAILURE)
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
            $this->addCliHelp($e->getMessage(), $action);

            return Command::FAILURE;
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            $this->addCliHelp($e->getMessage(), $action);

            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->error("An error occurred: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }

    /**
     * Handle module installation
     *
     * @param  string  $moduleName  The name of the module to install
     */
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

    /**
     * Handle module uninstallation
     *
     * @param  string  $moduleName  The name of the module to uninstall
     */
    private function handleUninstall(string $moduleName): void
    {
        $this->info("Uninstalling module: {$moduleName}...");
        $this->lifecycleService->uninstall($moduleName);
        $this->info("Module '{$moduleName}' uninstalled successfully!");
    }

    /**
     * Handle module enabling
     *
     * @param  string  $moduleName  The name of the module to enable
     */
    private function handleEnable(string $moduleName): void
    {
        $this->info("Enabling module: {$moduleName}...");
        $this->lifecycleService->enable($moduleName);
        $this->info("Module '{$moduleName}' enabled successfully!");
    }

    /**
     * Handle module disabling
     *
     * @param  string  $moduleName  The name of the module to disable
     */
    private function handleDisable(string $moduleName): void
    {
        $this->info("Disabling module: {$moduleName}...");
        $this->lifecycleService->disable($moduleName);
        $this->info("Module '{$moduleName}' disabled successfully!");
    }

    /**
     * Add CLI-specific help text to error messages
     *
     * Enhances generic error messages from the service layer with CLI-specific instructions.
     *
     * @param  string  $message  The error message from the service
     * @param  string  $action  The action that was attempted
     */
    private function addCliHelp(string $message, string $action): void
    {
        if (preg_match("/Please (install|enable|disable|uninstall) '([^']+)'/", $message, $matches)) {
            $suggestedAction = $matches[1];
            $suggestedModule = $matches[2];

            $this->newLine();
            $this->comment("To {$suggestedAction} '{$suggestedModule}', run:");
            $this->line("  php artisan module:manage {$suggestedModule} --action={$suggestedAction}");
        } elseif (preg_match('/Please (install|enable|disable|uninstall) the (dependent|following) modules/', $message, $matches)) {
            $this->newLine();
            $this->comment('Use the following command for each dependent module:');
            $this->line('  php artisan module:manage {module} --action={action}');
        }
    }
}
