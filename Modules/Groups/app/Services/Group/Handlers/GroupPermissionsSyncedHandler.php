<?php

namespace Modules\Groups\Services\Group\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Modules\Core\Models\User;
use Modules\Groups\Models\Group;

/**
 * Group Permissions Synced Handler
 *
 * Returns signal data when an administrator syncs a group's direct permissions.
 * Notifies all affected users (group members) that their permissions have changed.
 */
class GroupPermissionsSyncedHandler implements SignalHandlerInterface
{
    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['group.permissions.synced'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'Groups';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'GroupPermissionsSyncedHandler';
    }

    /** {@inheritdoc} */
    public function supports(string $event, mixed $data): bool
    {
        return is_array($data)
            && isset($data['user'])
            && $data['user'] instanceof User
            && isset($data['group'])
            && $data['group'] instanceof Group;
    }

    /** {@inheritdoc} */
    public function handle(mixed $data): ?array
    {
        /** @var Group $group */
        $group = $data['group'];

        return [
            'type' => 'info',
            'title' => 'Group Permissions Updated',
            'message' => "The permissions for group \"{$group->name}\" have been updated. Your access may have changed.",
            'url' => route('dashboard'),
            'category' => 'groups',
            'delivery' => 'broadcast',
            'action' => 'reload_permissions',
        ];
    }
}
