<?php

namespace Modules\Core\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Modules\AuditLog\Enums\AuditLogEvent;
use Modules\AuditLog\Traits\DispatchAuditLog;
use Modules\Core\Models\User;

class RegisteredUserController extends Controller
{
    use DispatchAuditLog;

    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Modules::Core/Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        /**
         * Disable automatic logging to prevent duplicate created event.
         * We'll log a specific registered event instead.
         */
        $user = User::withoutLoggingActivity(function () use ($request) {
            return User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
        });

        $this->dispatchAuditLog(
            event: AuditLogEvent::REGISTERED,
            model: $user,
            oldValues: null,
            newValues: [
                'name' => $user->name,
                'email' => $user->email,
            ],
            tags: 'authentication,registration',
            request: $request,
            userId: null
        );

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('guest.welcome', absolute: false));
    }
}
