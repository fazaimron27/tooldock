<?php

/**
 * Routine Command Registrar
 *
 * Registers Command Palette entries for the Routine module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Routine\Services;

use App\Services\Registry\CommandRegistry;

/**
 * Class RoutineCommandRegistrar
 *
 * Provides quick-access commands for navigating to the habit tracker
 * and creating new habits via the Command Palette.
 *
 * @see \App\Services\Registry\CommandRegistry
 */
class RoutineCommandRegistrar
{
    /**
     * Register all Command Palette commands for the Routine module.
     *
     * @param  CommandRegistry  $registry  The central command registry
     * @param  string  $moduleName  The module name identifier
     * @return void
     */
    public function register(CommandRegistry $registry, string $moduleName): void
    {
        $registry->registerMany($moduleName, 'Life OS', [
            [
                'label' => 'Routine',
                'route' => 'routine.index',
                'icon' => 'repeat',
                'permission' => 'routines.routine.view',
                'keywords' => ['routine', 'habits', 'tracker', 'streak', 'daily'],
                'order' => 60,
            ],
        ]);
    }
}
