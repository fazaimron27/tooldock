<?php

namespace Modules\Vault\Services;

use App\Services\Registry\CommandRegistry;

/**
 * Registers Command Palette commands for the Vault module.
 */
class VaultCommandRegistrar
{
    /**
     * Register all Command Palette commands for the Vault module.
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

        // Quick actions
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
