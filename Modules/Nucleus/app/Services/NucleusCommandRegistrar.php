<?php

/**
 * Nucleus Command Registrar.
 *
 * Registers Artisan commands for the Nucleus module.
 * Currently a no-op placeholder.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Nucleus\Services;

use App\Services\Registry\CommandRegistry;

/**
 * Class NucleusCommandRegistrar
 */
class NucleusCommandRegistrar
{
    /**
     * Register Nucleus module commands.
     *
     * @param  CommandRegistry  $registry
     * @param  string  $moduleName
     * @return void
     */
    public function register(CommandRegistry $registry, string $moduleName): void
    {
        // Add Nucleus-specific Artisan commands here as the module grows.
    }
}
