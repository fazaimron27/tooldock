<?php

namespace Modules\Core\Services\Auth\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Modules\Core\Models\User;

/**
 * Email Verified Handler
 *
 * Returns signal data when a user verifies their email address.
 * Sends a success notification to confirm email verification.
 */
class EmailVerifiedHandler implements SignalHandlerInterface
{
    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['auth.email.verified'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'System';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'EmailVerifiedHandler';
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
            'type' => 'success',
            'title' => 'Email Verified',
            'message' => 'Your email address has been successfully verified. You now have full access to all features.',
            'url' => route('dashboard'),
            'category' => 'system',
        ];
    }
}
