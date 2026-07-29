<?php

/**
 * HookInboundPolicy
 *
 * Authorization policy for HookInbound model. Owner-scoped
 * with permission-based access control.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Hook\Policies;

use Modules\Core\Models\User;
use Modules\Hook\Models\HookInbound;

/**
 * Class HookInboundPolicy
 */
class HookInboundPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('hook.inbound.view');
    }

    public function view(User $user, HookInbound $inbound): bool
    {
        return $user->can('hook.inbound.view')
            && $inbound->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('hook.inbound.create');
    }

    public function update(User $user, HookInbound $inbound): bool
    {
        return $user->can('hook.inbound.create')
            && $inbound->user_id === $user->id;
    }

    public function delete(User $user, HookInbound $inbound): bool
    {
        return $user->can('hook.inbound.delete')
            && $inbound->user_id === $user->id;
    }
}
