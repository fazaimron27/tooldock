<?php

/**
 * HookOutboundPolicy
 *
 * Authorization policy for HookOutbound model. Owner-scoped
 * with permission-based access control.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Hook\Policies;

use Modules\Core\Models\User;
use Modules\Hook\Models\HookOutbound;

/**
 * Class HookOutboundPolicy
 */
class HookOutboundPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('hook.outbound.view');
    }

    public function view(User $user, HookOutbound $outbound): bool
    {
        return $user->can('hook.outbound.view')
            && $outbound->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('hook.outbound.create');
    }

    public function update(User $user, HookOutbound $outbound): bool
    {
        return $user->can('hook.outbound.edit')
            && $outbound->user_id === $user->id;
    }

    public function delete(User $user, HookOutbound $outbound): bool
    {
        return $user->can('hook.outbound.delete')
            && $outbound->user_id === $user->id;
    }

    public function send(User $user, HookOutbound $outbound): bool
    {
        return $user->can('hook.outbound.send')
            && $outbound->user_id === $user->id;
    }
}
