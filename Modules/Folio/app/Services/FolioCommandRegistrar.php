<?php

/**
 * Folio Command Registrar.
 *
 * Registers Command Palette commands for the Folio module.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Folio\Services;

use App\Services\Registry\CommandRegistry;

/**
 * Registers Command Palette commands for the Folio module.
 */
class FolioCommandRegistrar
{
    /**
     * Register all Command Palette commands for the Folio module.
     *
     * @param  CommandRegistry  $registry
     * @param  string  $moduleName
     * @return void
     */
    public function register(CommandRegistry $registry, string $moduleName): void
    {
        $registry->registerMany($moduleName, 'Utilities', [
            [
                'label' => 'Folio',
                'route' => 'folio.index',
                'icon' => 'FileUser',
                'permission' => 'folio.folio.view',
                'keywords' => ['folio', 'resume', 'cv', 'portfolio', 'profile'],
                'order' => 30,
            ],
        ]);
    }
}
