<?php

/**
 * Verify Email Controller
 *
 * Handles email verification for authenticated users.
 * Integrates with AuditLog for tracking and Signal for notifications.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Core\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Registry\SignalHandlerRegistry;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Modules\AuditLog\Enums\AuditLogEvent;
use Modules\AuditLog\Traits\DispatchAuditLog;

/**
 * Class VerifyEmailController
 *
 * Processes email verification links and marks users as verified.
 * Logs verification events and sends success notifications via Signal.
 *
 * @see \Modules\AuditLog\Traits\DispatchAuditLog For audit logging
 * @see \App\Services\Registry\SignalHandlerRegistry For notifications
 */
class VerifyEmailController extends Controller
{
    use DispatchAuditLog;

    public function __construct(
        private readonly SignalHandlerRegistry $signalRegistry
    ) {}

    /**
     * Mark the authenticated user's email address as verified.
     *
     * Processes the verification link, marks email as verified,
     * logs the event, and sends a success notification via Signal.
     *
     * @param  EmailVerificationRequest  $request  The signed verification request
     * @return RedirectResponse Redirect to dashboard with verified flag
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        $user = $request->user();
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));

            $this->dispatchAuditLog(
                event: AuditLogEvent::EMAIL_VERIFIED,
                model: $user,
                oldValues: null,
                newValues: [
                    'email' => $user->email,
                    'verified_at' => now()->toIso8601String(),
                ],
                tags: 'authentication,email_verification',
                request: $request,
                userId: $user->id
            );

            $this->signalRegistry->dispatch('auth.email.verified', [
                'user' => $user,
            ]);
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
