<?php

namespace Modules\Vault\Services\Lock\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Modules\Core\Models\User;

/**
 * Vault PIN Changed Handler
 *
 * Returns signal data when a user sets or updates their vault PIN.
 * Notifies the user about the PIN change for security awareness.
 */
class VaultPinChangedHandler implements SignalHandlerInterface
{
    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['vault.pin.changed'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'Vault';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'VaultPinChangedHandler';
    }

    /** {@inheritdoc} */
    public function supports(string $event, mixed $data): bool
    {
        return is_array($data)
            && isset($data['user'])
            && $data['user'] instanceof User
            && isset($data['is_update']);
    }

    /** {@inheritdoc} */
    public function handle(mixed $data): ?array
    {
        /** @var User $user */
        $user = $data['user'];
        $isUpdate = $data['is_update'];
        $actionUrl = $user->can('vaults.vault.view') ? route('vault.index') : null;

        if ($isUpdate) {
            return [
                'type' => 'alert',
                'title' => 'Vault PIN Changed',
                'message' => 'Your Vault PIN was changed. If you did not make this change, please update your PIN immediately.',
                'url' => $actionUrl,
                'category' => 'vault',
            ];
        }

        return [
            'type' => 'success',
            'title' => 'Vault PIN Set',
            'message' => 'Your Vault PIN was set successfully. Your vault is now protected with a PIN.',
            'url' => $actionUrl,
            'category' => 'vault',
        ];
    }
}
