<?php

namespace Modules\Core\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Modules\AuditLog\App\Enums\AuditLogEvent;
use Modules\AuditLog\App\Traits\DispatchAuditLog;

class VerifyEmailController extends Controller
{
    use DispatchAuditLog;

    /**
     * Mark the authenticated user's email address as verified.
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
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
