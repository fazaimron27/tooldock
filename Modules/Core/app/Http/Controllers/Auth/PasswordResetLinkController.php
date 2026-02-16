<?php

/**
 * Password Reset Link Controller.
 *
 * Handles password reset link request form display and
 * sending reset links with audit logging.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Core\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\AuditLog\Enums\AuditLogEvent;
use Modules\AuditLog\Traits\DispatchAuditLog;
use Modules\Core\Models\User;

class PasswordResetLinkController extends Controller
{
    use DispatchAuditLog;

    /**
     * Display the password reset link request view.
     *
     * @return Response Inertia forgot password page response
     */
    public function create(): Response
    {
        return Inertia::render('Modules::Core/Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @param  Request  $request  The HTTP request containing the email
     * @return RedirectResponse Redirect back with status
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status == Password::RESET_LINK_SENT) {
            $user = User::where('email', $request->email)->first();
            if ($user) {
                $this->dispatchAuditLog(
                    event: AuditLogEvent::PASSWORD_RESET_REQUESTED,
                    model: $user,
                    oldValues: null,
                    newValues: [
                        'email' => $user->email,
                        'requested_at' => now()->toIso8601String(),
                    ],
                    tags: 'authentication,password_reset',
                    request: $request,
                    userId: $user->id
                );
            }

            return back()->with('status', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
