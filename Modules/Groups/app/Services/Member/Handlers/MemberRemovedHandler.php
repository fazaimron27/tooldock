<?php

namespace Modules\Groups\Services\Member\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Modules\Core\Models\User;
use Modules\Groups\Models\Group;

/**
 * Member Removed Handler
 *
 * Returns signal data when a user is removed from a group.
 * Notifies the user about being removed from the group.
 */
class MemberRemovedHandler implements SignalHandlerInterface
{
    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['groups.member.removed'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'Groups';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'MemberRemovedHandler';
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
            'type' => 'warning',
            'title' => 'Removed from Group',
            'message' => "You have been removed from the group \"{$group->name}\". Your permissions may have changed.",
            'url' => route('dashboard'),
            'category' => 'groups',
            'delivery' => 'broadcast',
            'action' => 'reload_permissions',
        ];
    }
}
