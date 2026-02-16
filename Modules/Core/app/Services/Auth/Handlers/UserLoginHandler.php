<?php

namespace Modules\Core\Services\Auth\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Modules\Core\Models\User;

/**
 * User Login Handler
 *
 * Returns signal data when a user logs in successfully.
 * Notifies the user about the login with IP address information.
 */
class UserLoginHandler implements SignalHandlerInterface
{
    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['auth.login'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'System';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'UserLoginHandler';
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
        /** @var User $user */
        $user = $data['user'];
        $ip = $data['ip'];

        return [
            'type' => 'info',
            'title' => 'New Login',
            'message' => "You logged in from IP address: {$ip}. If this wasn't you, please change your password immediately.",
            'url' => route('profile.edit'),
            'category' => 'login',
        ];
    }
}
