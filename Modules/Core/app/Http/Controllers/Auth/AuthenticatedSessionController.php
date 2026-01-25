<?php

/**
 * Authenticated Session Controller
 *
 * Handles user authentication including login and logout functionality.
 * Integrates with AuditLog for tracking authentication events and Signal
 * for sending login notifications to users.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Core\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Modules\AuditLog\Enums\AuditLogEvent;
use Modules\AuditLog\Traits\DispatchAuditLog;
use Modules\Core\Http\Requests\Auth\LoginRequest;
use Modules\Signal\Traits\SendsSignalNotifications;

/**
 * Class AuthenticatedSessionController
 *
 * Manages user session lifecycle including authentication and logout.
 * Logs all authentication events and sends security notifications.
 *
 * @see \Modules\Core\Http\Requests\Auth\LoginRequest For login validation
 * @see \Modules\AuditLog\Traits\DispatchAuditLog For audit logging
 * @see \Modules\Signal\Traits\SendsSignalNotifications For login notifications
 */
class AuthenticatedSessionController extends Controller
{
    use DispatchAuditLog;
    use SendsSignalNotifications;

    /**
     * Display the login view.
     *
     * Renders the login page with password reset link availability.
     *
     * @return Response Inertia login page response
     */
    public function create(): Response
    {
        return Inertia::render('Modules::Core/Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     *
     * Authenticates the user, regenerates session, logs the event to
     * audit log, and sends a login notification via Signal.
     *
     * @param  LoginRequest  $request  The validated login request
     * @return RedirectResponse Redirect to intended destination
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();
        $ip = $request->ip();

        if ($user) {
            $this->dispatchAuditLog(
                event: AuditLogEvent::LOGIN,
                model: $user,
                oldValues: null,
                newValues: [
                    'email' => $user->email,
                    'logged_in_at' => now()->toIso8601String(),
                ],
                tags: 'authentication,login',
                request: $request,
                userId: $user->id
            );

            $this->signalInfo(
                $user,
                'New Login',
                "You logged in from IP address: {$ip}. If this wasn't you, please change your password immediately.",
                route('profile.edit'),
                'System',
                'login'
            );
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     *
     * Logs out the user, invalidates session, regenerates CSRF token,
     * and logs the logout event to audit log.
     *
     * @param  Request  $request  The HTTP request
     * @return RedirectResponse Redirect to home page
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        if ($user) {
            $this->dispatchAuditLog(
                event: AuditLogEvent::LOGOUT,
                model: $user,
                oldValues: [
                    'email' => $user->email,
                    'logged_out_at' => now()->toIso8601String(),
                ],
                newValues: null,
                tags: 'authentication,logout',
                request: $request,
                userId: $user->id
            );
        }

        return redirect('/');
    }
}
