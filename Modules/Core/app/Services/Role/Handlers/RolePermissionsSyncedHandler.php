<?php

/**
 * Role Permissions Synced Handler.
 *
 * Signal handler that notifies users when their role
 * permissions have been modified by an administrator.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Core\Services\Role\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Modules\Core\Models\Role;
use Modules\Core\Models\User;

/**
 * Role Permissions Synced Handler
 *
 * Returns signal data when an administrator syncs a role's permissions.
 * Notifies all affected users that their permissions have changed.
 */
class RolePermissionsSyncedHandler implements SignalHandlerInterface
{
    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['role.permissions.synced'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'System';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'RolePermissionsSyncedHandler';
    }

    /** {@inheritdoc} */
    public function supports(string $event, mixed $data): bool
    {
        return is_array($data)
            && isset($data['user'])
            && $data['user'] instanceof User
            && isset($data['role'])
            && $data['role'] instanceof Role;
    }

    /** {@inheritdoc} */
    public function handle(mixed $data): ?array
    {
        /** @var Role $role */
        $role = $data['role'];

        return [
            'type' => 'info',
            'title' => 'Permissions Updated',
            'message' => "The permissions for role \"{$role->name}\" have been updated. Your access may have changed.",
            'url' => route('dashboard'),
            'category' => 'system',
            'delivery' => 'broadcast',
            'action' => 'reload_permissions',
        ];
    }
}
