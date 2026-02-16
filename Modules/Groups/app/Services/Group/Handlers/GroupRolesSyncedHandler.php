<?php

/**
 * Group Roles Synced Handler.
 *
 * Handles cache invalidation when group roles are synced.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Groups\Services\Group\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Modules\Core\Models\User;
use Modules\Groups\Models\Group;

/**
 * Group Roles Synced Handler
 *
 * Returns signal data when an administrator syncs a group's role assignments.
 * Notifies all affected users (group members) that their permissions have changed.
 */
class GroupRolesSyncedHandler implements SignalHandlerInterface
{
    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['group.roles.synced'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'Groups';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'GroupRolesSyncedHandler';
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
            'title' => 'Group Roles Updated',
            'message' => "The roles for group \"{$group->name}\" have been updated. Your access may have changed.",
            'url' => route('dashboard'),
            'category' => 'groups',
            'delivery' => 'broadcast',
            'action' => 'reload_permissions',
        ];
    }
}
