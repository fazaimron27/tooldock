<?php

/**
 * New User Admin Handler.
 *
 * Signal handler that notifies administrators
 * when a new user account is registered.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Core\Services\Auth\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Modules\Core\Models\User;

/**
 * New User Admin Handler
 *
 * Returns signal data to notify admins when a new user registers.
 * Alerts administrators about new user registrations.
 */
class NewUserAdminHandler implements SignalHandlerInterface
{
    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['user.registered.admin'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'System';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'NewUserAdminHandler';
    }

    /** {@inheritdoc} */
    public function supports(string $event, mixed $data): bool
    {
        return is_array($data)
            && isset($data['user'], $data['new_user'])
            && $data['user'] instanceof User
            && $data['new_user'] instanceof User;
    }

    /** {@inheritdoc} */
    public function handle(mixed $data): ?array
    {
        /** @var User $newUser */
        $newUser = $data['new_user'];
        /** @var User $admin */
        $admin = $data['user'];
        $actionUrl = $admin->can('core.users.view') ? route('core.users.index') : null;

        return [
            'type' => 'info',
            'title' => 'New User Registered',
            'message' => "A new user \"{$newUser->name}\" ({$newUser->email}) has registered.",
            'url' => $actionUrl,
            'category' => 'system',
        ];
    }
}
