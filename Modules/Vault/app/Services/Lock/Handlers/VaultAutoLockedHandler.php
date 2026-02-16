<?php

/**
 * Vault Auto-Locked Handler
 *
 * Signal handler that fires when a vault is automatically locked due to timeout.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Vault\Services\Lock\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Modules\Core\Models\User;

/**
 * Vault Auto-Locked Handler
 *
 * Returns signal data when a user's vault is automatically locked due to timeout.
 * Notifies the user about the vault being auto-locked for security.
 */
class VaultAutoLockedHandler implements SignalHandlerInterface
{
    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['vault.autolocked'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'Vault';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'VaultAutoLockedHandler';
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
            'type' => 'warning',
            'title' => 'Vault Auto-Locked',
            'message' => 'Your vault was automatically locked due to inactivity. Please unlock to continue accessing your secrets.',
            'url' => $actionUrl,
            'category' => 'vault',
        ];
    }
}
