<?php

/**
 * Vault Command Registrar
 *
 * Registers Command Palette entries for the Vault module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Vault\Services;

use App\Services\Registry\CommandRegistry;

/**
 * Class VaultCommandRegistrar
 *
 * Provides quick-access commands for navigating to and creating vault items.
 *
 * @see \App\Services\Registry\CommandRegistry
 */
class VaultCommandRegistrar
{
    /**
     * Register all Command Palette commands for the Vault module.
     *
     * @param  CommandRegistry  $registry  The central command registry
     * @param  string  $moduleName  The module name identifier
     * @return void
     */
    public function register(CommandRegistry $registry, string $moduleName): void
    {
        $registry->registerMany($moduleName, 'Utilities', [
            [
                'label' => 'Vault',
                'route' => 'vault.index',
                'icon' => 'shield-check',
                'permission' => 'vaults.vault.view',
                'keywords' => ['vault', 'secure', 'passwords', 'secrets', 'credentials'],
                'order' => 10,
            ],
        ]);

        $registry->registerMany($moduleName, 'Quick Actions', [
            [
                'label' => 'Create Vault',
                'route' => 'vault.create',
                'icon' => 'plus',
                'permission' => 'vaults.vault.create',
                'keywords' => ['new', 'add', 'vault', 'secure', 'password'],
                'order' => 20,
            ],
        ]);
    }
}
