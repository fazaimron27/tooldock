<?php

/**
 * New Password Controller
 *
 * Handles password reset completion after user clicks email link.
 * Integrates with AuditLog for tracking and Signal for notifications.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Core\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\AuditLog\Enums\AuditLogEvent;
use Modules\AuditLog\Traits\DispatchAuditLog;
use Modules\Signal\Traits\SendsSignalNotifications;

/**
 * Class NewPasswordController
 *
 * Manages the password reset flow after token verification.
 * Logs password resets and sends security alerts via Signal.
 *
 * @see \Modules\AuditLog\Traits\DispatchAuditLog For audit logging
 * @see \Modules\Signal\Traits\SendsSignalNotifications For security alerts
 */
class NewPasswordController extends Controller
{
    use DispatchAuditLog;
    use SendsSignalNotifications;

    /**
     * Display the password reset view.
     *
     * Shows the form for entering a new password after clicking
     * the reset link from email.
     *
     * @param  Request  $request  The HTTP request with token and email
     * @return Response Inertia reset password page
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Modules::Core/Auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]);
    }

    /**
     * Handle an incoming new password request.
     *
     * Validates the token, updates the password, logs the event,
     * and sends a security notification via Signal.
     *
     * @param  Request  $request  The HTTP request with reset data
     * @return RedirectResponse Redirect to login on success
     *
     * @throws ValidationException When token is invalid or expired
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));

                $this->dispatchAuditLog(
                    event: AuditLogEvent::PASSWORD_RESET,
                    model: $user,
                    oldValues: null,
                    newValues: [
                        'email' => $user->email,
                        'reset_at' => now()->toIso8601String(),
                    ],
                    tags: 'authentication,password_reset',
                    request: $request,
                    userId: $user->id
                );

                $this->signalAlert(
                    $user,
                    'Password Reset Complete',
                    'Your password was reset via email link. If you did not request this, please contact support immediately.',
                    route('login'),
                    'System',
                    'security'
                );
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
