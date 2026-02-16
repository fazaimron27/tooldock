<?php

namespace Modules\Groups\Services\Member\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Modules\Core\Models\User;
use Modules\Groups\Models\Group;

/**
 * Member Transferred Handler
 *
 * Returns signal data when a user is transferred between groups.
 * Notifies the user about the group transfer.
 */
class MemberTransferredHandler implements SignalHandlerInterface
{
    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['groups.member.transferred'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'Groups';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'MemberTransferredHandler';
    }

    /** {@inheritdoc} */
    public function supports(string $event, mixed $data): bool
    {
        return is_array($data)
            && isset($data['user'])
            && $data['user'] instanceof User
            && isset($data['source_group'])
            && $data['source_group'] instanceof Group
            && isset($data['target_group'])
            && $data['target_group'] instanceof Group;
    }

    /** {@inheritdoc} */
    public function handle(mixed $data): ?array
    {
        /** @var User $user */
        $user = $data['user'];
        /** @var Group $sourceGroup */
        $sourceGroup = $data['source_group'];
        /** @var Group $targetGroup */
        $targetGroup = $data['target_group'];

        $actionUrl = $user->can('groups.group.view') ? route('groups.groups.show', $targetGroup) : null;

        return [
            'type' => 'info',
            'title' => 'Transferred Between Groups',
            'message' => "You have been transferred from \"{$sourceGroup->name}\" to \"{$targetGroup->name}\". Your permissions may have changed.",
            'url' => $actionUrl,
            'category' => 'groups',
            'delivery' => 'broadcast',
            'action' => 'reload_permissions',
        ];
    }
}
