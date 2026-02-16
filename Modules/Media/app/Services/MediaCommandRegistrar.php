<?php

/**
 * Media Command Registrar.
 *
 * Registers Command Palette commands for the Media module.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Media\Services;

use App\Services\Registry\CommandRegistry;

/**
 * Registers Command Palette commands for the Media module.
 */
class MediaCommandRegistrar
{
    /**
     * Register all Command Palette commands for the Media module.
     *
     * @param  CommandRegistry  $registry
     * @param  string  $moduleName
     * @return void
     */
    public function register(CommandRegistry $registry, string $moduleName): void
    {
        $registry->registerMany($moduleName, 'System', [
            [
                'label' => 'Media',
                'route' => 'media.index',
                'icon' => 'image',
                'permission' => 'media.files.view',
                'keywords' => ['media', 'files', 'images', 'upload', 'library'],
                'order' => 40,
            ],
        ]);
    }
}
