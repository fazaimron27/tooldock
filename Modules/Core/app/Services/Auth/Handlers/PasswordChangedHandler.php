<?php

namespace Modules\Core\Services\Auth\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Modules\Core\Models\User;

/**
 * Password Changed Handler
 *
 * Returns signal data when a user's password is changed.
 * Notifies the user about the password change for security.
 */
class PasswordChangedHandler implements SignalHandlerInterface
{
    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['auth.password.changed'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'System';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'PasswordChangedHandler';
    }

    /** {@inheritdoc} */
    public function supports(string $event, mixed $data): bool
    {
        return is_array($data)
            && isset($data['user'])
            && $data['user'] instanceof User;
    }

    /** {@inheritdoc} */
    public function handle(mixed $data): ?array
    {
        return [
            'type' => 'info',
            'title' => 'Password Changed',
            'message' => "Your password has been successfully changed. If you didn't make this change, please contact support immediately.",
            'url' => route('profile.edit'),
            'category' => 'security',
        ];
    }
}
