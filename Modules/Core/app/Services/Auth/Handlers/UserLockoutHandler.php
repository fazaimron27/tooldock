<?php

/**
 * User Lockout Handler.
 *
 * Signal handler that sends security alerts when
 * a user account is locked due to failed attempts.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Core\Services\Auth\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Modules\Core\Models\User;

/**
 * User Lockout Handler
 *
 * Returns signal data when a user account is locked due to multiple failed login attempts.
 * Sends a security alert to the user about the lockout.
 */
class UserLockoutHandler implements SignalHandlerInterface
{
    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['auth.lockout'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'System';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'UserLockoutHandler';
    }

    /** {@inheritdoc} */
    public function supports(string $event, mixed $data): bool
    {
        return is_array($data)
            && isset($data['user'])
            && $data['user'] instanceof User
            && isset($data['ip']);
    }

    /** {@inheritdoc} */
    public function handle(mixed $data): ?array
    {
        $ip = $data['ip'];

        return [
            'type' => 'alert',
            'title' => 'Account Temporarily Locked',
            'message' => "Your account was temporarily locked after multiple failed login attempts from IP: {$ip}. If this wasn't you, please change your password immediately.",
            'url' => route('password.request'),
            'category' => 'security',
        ];
    }
}
