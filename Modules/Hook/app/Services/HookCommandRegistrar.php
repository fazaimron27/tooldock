<?php

/**
 * Hook Command Registrar.
 *
 * Registers Command Palette commands for the Hook module.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Hook\Services;

use App\Services\Registry\CommandRegistry;

/**
 * Registers Command Palette commands for the Hook module.
 */
class HookCommandRegistrar
{
    /**
     * Register all Command Palette commands for the Hook module.
     *
     * @param  CommandRegistry  $registry
     * @param  string  $moduleName
     * @return void
     */
    public function register(CommandRegistry $registry, string $moduleName): void
    {
        $registry->registerMany($moduleName, 'Developer', [
            [
                'label' => 'Hook',
                'route' => 'hook.index',
                'icon' => 'Webhook',
                'permission' => 'hook.inbound.view',
                'keywords' => ['hook', 'webhook', 'inbound', 'outbound', 'trigger'],
                'order' => 20,
            ],
        ]);
    }
}
