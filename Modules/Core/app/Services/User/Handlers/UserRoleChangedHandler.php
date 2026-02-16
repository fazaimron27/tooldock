<?php

namespace Modules\Core\Services\User\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Modules\Core\Models\User;

/**
 * User Role Changed Handler
 *
 * Returns signal data when an administrator changes a user's roles.
 * Notifies the affected user about their role changes.
 */
class UserRoleChangedHandler implements SignalHandlerInterface
{
    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['user.roles.changed'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'System';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'UserRoleChangedHandler';
    }

    /** {@inheritdoc} */
    public function supports(string $event, mixed $data): bool
    {
        return is_array($data)
            && isset($data['user'])
            && $data['user'] instanceof User
            && isset($data['old_roles'])
            && isset($data['new_roles']);
    }

    /** {@inheritdoc} */
    public function handle(mixed $data): ?array
    {
        /** @var array<string> $oldRoles */
        $oldRoles = $data['old_roles'];
        /** @var array<string> $newRoles */
        $newRoles = $data['new_roles'];

        // Only notify if roles actually changed
        if ($oldRoles === $newRoles) {
            return null;
        }

        $oldRolesText = empty($oldRoles) ? 'none' : implode(', ', $oldRoles);
        $newRolesText = empty($newRoles) ? 'none' : implode(', ', $newRoles);

        return [
            'type' => 'info',
            'title' => 'Your Roles Changed',
            'message' => "An administrator changed your roles from [{$oldRolesText}] to [{$newRolesText}]. Your permissions may have changed.",
            'url' => route('dashboard'),
            'category' => 'system',
            'delivery' => 'broadcast',
            'action' => 'reload_permissions',
        ];
    }
}
