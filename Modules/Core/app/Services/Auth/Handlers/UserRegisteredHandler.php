<?php

/**
 * User Registered Handler.
 *
 * Signal handler that sends welcome notifications
 * to newly registered users.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Core\Services\Auth\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Modules\Core\Models\User;

/**
 * User Registered Handler
 *
 * Returns signal data when a new user successfully registers.
 * Sends a welcome notification to the newly registered user.
 */
class UserRegisteredHandler implements SignalHandlerInterface
{
    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['user.registered'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'System';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'UserRegisteredHandler';
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
        /** @var User $user */
        $user = $data['user'];
        $actionUrl = route('dashboard');

        return [
            'type' => 'info',
            'title' => "Welcome aboard, {$user->name}!",
            'message' => 'Your account has been created with guest access. To request additional permissions or roles, please contact your administrator.',
            'url' => $actionUrl,
            'category' => 'system',
        ];
    }
}
