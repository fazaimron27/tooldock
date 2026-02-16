<?php

namespace Modules\Media\Services;

use App\Services\Registry\CommandRegistry;

/**
 * Registers Command Palette commands for the Media module.
 */
class MediaCommandRegistrar
{
    /**
     * Register all Command Palette commands for the Media module.
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
