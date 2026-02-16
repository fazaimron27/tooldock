<?php

namespace Modules\Core\Services\Auth\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Modules\Core\Models\User;

/**
 * Password Reset Handler
 *
 * Returns signal data when a user resets their password via email link.
 * Sends a security alert about the password reset.
 */
class PasswordResetHandler implements SignalHandlerInterface
{
    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['auth.password.reset'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'System';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'PasswordResetHandler';
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
        $actionUrl = route('login');

        return [
            'type' => 'alert',
            'title' => 'Password Reset Complete',
            'message' => 'Your password was reset via email link. If you did not request this, please contact support immediately.',
            'url' => $actionUrl,
            'category' => 'security',
        ];
    }
}
