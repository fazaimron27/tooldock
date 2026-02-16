<?php

namespace Modules\Vault\Services\Lock\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Modules\Core\Models\User;

/**
 * Vault Locked Handler
 *
 * Returns signal data when a user manually locks their vault.
 * Notifies the user about the vault being locked.
 */
class VaultLockedHandler implements SignalHandlerInterface
{
    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['vault.locked'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'Vault';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'VaultLockedHandler';
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
        $actionUrl = $user->can('vaults.vault.view') ? route('vault.lock') : null;

        return [
            'type' => 'info',
            'title' => 'Vault Locked',
            'message' => 'Your vault has been locked. Your secrets are now protected.',
            'url' => $actionUrl,
            'category' => 'vault',
        ];
    }
}
