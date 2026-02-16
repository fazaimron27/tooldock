<?php

namespace Modules\Groups\Services\Member\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Modules\Core\Models\User;
use Modules\Groups\Models\Group;

/**
 * Member Added Handler
 *
 * Returns signal data when a user is added to a group.
 * Notifies the user about being added to the group.
 */
class MemberAddedHandler implements SignalHandlerInterface
{
    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['groups.member.added'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'Groups';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'MemberAddedHandler';
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
        /** @var User $user */
        $user = $data['user'];
        /** @var Group $group */
        $group = $data['group'];

        $actionUrl = $user->can('groups.group.view') ? route('groups.groups.show', $group) : null;

        return [
            'type' => 'info',
            'title' => 'Added to Group',
            'message' => "You have been added to the group \"{$group->name}\". Your permissions may have changed.",
            'url' => $actionUrl,
            'category' => 'groups',
            'delivery' => 'broadcast',
            'action' => 'reload_permissions',
        ];
    }
}
