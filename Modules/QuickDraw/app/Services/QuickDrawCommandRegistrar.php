<?php

/**
 * QuickDraw Command Registrar.
 *
 * Registers Command Palette commands for the QuickDraw module.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\QuickDraw\Services;

use App\Services\Registry\CommandRegistry;

/**
 * Registers Command Palette commands for the QuickDraw module.
 */
class QuickDrawCommandRegistrar
{
    /**
     * Register all Command Palette commands for the QuickDraw module.
     *
     * @param  CommandRegistry  $registry
     * @param  string  $moduleName
     * @return void
     */
    public function register(CommandRegistry $registry, string $moduleName): void
    {
        $registry->registerMany($moduleName, 'Utilities', [
            [
                'label' => 'QuickDraw',
                'route' => 'quickdraw.index',
                'icon' => 'PenTool',
                'permission' => 'quickdraw.draw.view',
                'keywords' => ['quickdraw', 'draw', 'sketch', 'canvas', 'whiteboard', 'diagram'],
                'order' => 20,
            ],
        ]);
    }
}
