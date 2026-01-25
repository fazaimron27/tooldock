<?php

/**
 * Send Lockout Notification Listener
 *
 * Listens for account lockout events and sends security notifications
 * to affected users via the Signal module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Core\Listeners;

use Illuminate\Auth\Events\Lockout;
use Modules\Core\Models\User;
use Modules\Signal\Traits\SendsSignalNotifications;

/**
 * Class SendLockoutNotification
 *
 * Handles account lockout events triggered by rate limiting after
 * multiple failed login attempts. Sends security alerts to users
 * to notify them of potential unauthorized access attempts.
 *
 * @see \Illuminate\Auth\Events\Lockout For the lockout event
 * @see \Modules\Signal\Traits\SendsSignalNotifications For notifications
 */
class SendLockoutNotification
{
    use SendsSignalNotifications;

    /**
     * Handle the lockout event.
     *
     * Sends a security alert to the user whose account was locked,
     * including the IP address that triggered the lockout.
     *
     * @param  Lockout  $event  The lockout event containing request data
     * @return void
     */
    public function handle(Lockout $event): void
    {
        $request = $event->request;
        $email = $request->input('email');
        $ip = $request->ip();

        if (! $email) {
            return;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return;
        }

        $this->signalAlert(
            $user,
            'Account Temporarily Locked',
            "Your account was temporarily locked after multiple failed login attempts from IP: {$ip}. If this wasn't you, please change your password immediately.",
            route('password.request'),
            'System',
            'security'
        );
    }
}
