<?php

/**
 * Email Changed Handler.
 *
 * Signal handler that notifies users when their
 * email address has been changed.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Core\Services\User\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Modules\Core\Models\User;

/**
 * Email Changed Handler
 *
 * Returns signal data when a user changes their email address.
 * Sends a security alert about the email change.
 */
class EmailChangedHandler implements SignalHandlerInterface
{
    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['user.email.changed'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'System';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'EmailChangedHandler';
    }

    /** {@inheritdoc} */
    public function supports(string $event, mixed $data): bool
    {
        return is_array($data)
            && isset($data['user'], $data['old_email'])
            && $data['user'] instanceof User;
    }

    /** {@inheritdoc} */
    public function handle(mixed $data): ?array
    {
        /** @var User $user */
        $user = $data['user'];
        $oldEmail = $data['old_email'];
        $actionUrl = route('profile.edit');

        return [
            'type' => 'alert',
            'title' => 'Email Address Changed',
            'message' => "Your email was changed from {$oldEmail} to {$user->email}. Email verification is required. If you did not make this change, please contact support immediately.",
            'url' => $actionUrl,
            'category' => 'security',
        ];
    }
}
