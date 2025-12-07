<?php

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

class AuthenticatedSessionController extends Controller
{
    use DispatchAuditLog;

    /**
     * Display the login view.
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
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

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
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
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
