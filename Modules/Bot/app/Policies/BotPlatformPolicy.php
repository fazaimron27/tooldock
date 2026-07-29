<?php

/**
 * BotPlatformPolicy
 *
 * Authorization policy for BotPlatform model. Owner-scoped
 * with granular permission-based access control.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Bot\Policies;

use Modules\Bot\Models\BotPlatform;
use Modules\Core\Models\User;

class BotPlatformPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('bot.bridge.view');
    }

    public function view(User $user, BotPlatform $platform): bool
    {
        return $user->can('bot.bridge.view')
            && $platform->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('bot.bridge.create');
    }

    public function update(User $user, BotPlatform $platform): bool
    {
        return $user->can('bot.bridge.edit')
            && $platform->user_id === $user->id;
    }

    public function delete(User $user, BotPlatform $platform): bool
    {
        return $user->can('bot.bridge.delete')
            && $platform->user_id === $user->id;
    }

    public function test(User $user, BotPlatform $platform): bool
    {
        return $user->can('bot.bridge.test')
            && $platform->user_id === $user->id;
    }
}
