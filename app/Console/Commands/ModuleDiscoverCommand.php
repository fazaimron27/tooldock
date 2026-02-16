<?php

/**
 * Module Discover Command.
 *
 * Artisan command that scans the Modules directory, registers all discovered
 * modules in the database, and optionally installs them with the --install flag.
 * Supports running seeders during bulk installation via the --seed flag.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace App\Console\Commands;

use App\Services\Modules\ModuleLifecycleService;
use Illuminate\Console\Command;

/**
 * Artisan command to discover and register modules.
 *
 * Scans the Modules directory and registers all found modules in the database.
 * Optionally installs all discovered modules with the --install flag.
 *
 * @see ModuleLifecycleService Handles module discovery and installation logic
 */
class ModuleDiscoverCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:discover {--install : Automatically install all discovered modules} {--seed : Run seeders during installation (only used with --install)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Discover and register all available modules in the database';

    /**
     * Create a new command instance.
     *
     * @param  ModuleLifecycleService  $lifecycleService  Service for module lifecycle operations
     */
    public function __construct(
        private ModuleLifecycleService $lifecycleService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command
     *
     * Discovers modules, registers them in the database, and optionally installs them.
     * Continues installing remaining modules even if one fails.
     *
     * @return int Command exit code (SUCCESS or FAILURE)
     */
    public function handle(): int
    {
        $this->info('Discovering modules...');

        $discoveredModules = $this->lifecycleService->discoverAndRegisterAll();

        if (empty($discoveredModules)) {
            $this->warn('No modules found in the Modules directory.');

            return Command::SUCCESS;
        }

        $this->info('Discovered '.count($discoveredModules).' module(s):');
        foreach ($discoveredModules as $moduleName) {
            $this->line("  - {$moduleName}");
        }

        if ($this->option('install')) {
            $this->newLine();
            $this->info('Installing discovered modules...');

            $withSeed = $this->option('seed');

            foreach ($discoveredModules as $moduleName) {
                try {
                    $this->info("Installing module: {$moduleName}...");
                    $this->lifecycleService->install($moduleName, $withSeed);
                    $this->info("Module '{$moduleName}' installed successfully!");
                } catch (\Exception $e) {
                    $this->error("Failed to install module '{$moduleName}': {$e->getMessage()}");
                }
            }

            $this->newLine();
            $this->info('All modules have been processed.');
        } else {
            $this->newLine();
            $this->info('Modules have been registered in the database.');
            $this->comment('To install a module, run: php artisan module:manage {module} --action=install');
        }

        return Command::SUCCESS;
    }
}
