<?php

/**
 * Password Controller
 *
 * Handles password updates for authenticated users.
 * Integrates with AuditLog for tracking changes and Signal
 * for sending security notifications.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Core\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Modules\AuditLog\Enums\AuditLogEvent;
use Modules\AuditLog\Traits\DispatchAuditLog;
use Modules\Signal\Traits\SendsSignalNotifications;

/**
 * Class PasswordController
 *
 * Manages password changes for authenticated users from their profile.
 * Logs password changes and sends security alerts via Signal.
 *
 * @see \Modules\AuditLog\Traits\DispatchAuditLog For audit logging
 * @see \Modules\Signal\Traits\SendsSignalNotifications For security alerts
 */
class PasswordController extends Controller
{
    use DispatchAuditLog;
    use SendsSignalNotifications;

    /**
     * Update the user's password.
     *
     * Validates current password, updates to new password, logs the change
     * to audit log, and sends a security notification via Signal.
     *
     * @param  Request  $request  The HTTP request with password data
     * @return RedirectResponse Redirect back with success message
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = $request->user();

        /**
         * Disable automatic logging to prevent duplicate updated event.
         * We'll log a specific password_changed event instead.
         */
        \Modules\Core\Models\User::withoutLoggingActivity(function () use ($user, $validated) {
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);
        });

        $this->dispatchAuditLog(
            event: AuditLogEvent::PASSWORD_CHANGED,
            model: $user,
            oldValues: null,
            newValues: [
                'email' => $user->email,
                'changed_at' => now()->toIso8601String(),
            ],
            tags: 'authentication,password_change',
            request: $request,
            userId: $user->id
        );

        $this->signalAlert(
            $user,
            'Password Changed',
            'Your password was changed successfully. If you did not make this change, please contact support immediately.',
            route('profile.edit'),
            'System',
            'security'
        );

        return back()->with('success', 'Password updated successfully.');
    }
}
