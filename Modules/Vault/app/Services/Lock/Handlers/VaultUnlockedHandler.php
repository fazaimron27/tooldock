<?php

/**
 * Vault Unlocked Handler
 *
 * Signal handler that fires when a user unlocks their vault.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Vault\Services\Lock\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Modules\Core\Models\User;

/**
 * Vault Unlocked Handler
 *
 * Returns signal data when a user unlocks their vault.
 * Notifies the user about the vault being unlocked.
 */
class VaultUnlockedHandler implements SignalHandlerInterface
{
    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['vault.unlocked'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'Vault';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'VaultUnlockedHandler';
    }

    /** {@inheritdoc} */
    public function supports(string $event, mixed $data): bool
    {
        return is_array($data)
            && isset($data['user'])
            && $data['user'] instanceof User;
    }

    /** {@inheritdoc} */
    public function handle(mixed $data): ?array
    {
        /** @var User $user */
        $user = $data['user'];
        $actionUrl = $user->can('vaults.vault.view') ? route('vault.index') : null;

        return [
            'type' => 'info',
            'title' => 'Vault Unlocked',
            'message' => 'Your vault has been unlocked. Remember to lock it when you are done.',
            'url' => $actionUrl,
            'category' => 'vault',
        ];
    }
}
