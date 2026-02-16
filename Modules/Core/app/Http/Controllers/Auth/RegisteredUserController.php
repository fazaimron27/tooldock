<?php

/**
 * Registered User Controller
 *
 * Handles new user registration and welcome notifications.
 * Integrates with AuditLog for tracking and Signal for notifications.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Core\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Registry\SignalHandlerRegistry;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Modules\AuditLog\Enums\AuditLogEvent;
use Modules\AuditLog\Traits\DispatchAuditLog;
use Modules\Core\Constants\Roles;
use Modules\Core\Models\User;

/**
 * Class RegisteredUserController
 *
 * Manages user registration including account creation, welcome
 * notifications, and admin notifications for new users.
 *
 * @see \Modules\AuditLog\Traits\DispatchAuditLog For audit logging
 * @see \App\Services\Registry\SignalHandlerRegistry For notifications
 */
class RegisteredUserController extends Controller
{
    use DispatchAuditLog;

    public function __construct(
        private readonly SignalHandlerRegistry $signalRegistry
    ) {}

    /**
     * Display the registration view.
     *
     * @return Response Inertia registration page
     */
    public function create(): Response
    {
        return Inertia::render('Modules::Core/Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * Creates the user account, logs registration, sends welcome
     * notification, and notifies admins of new user.
     *
     * @param  Request  $request  The HTTP request with registration data
     * @return RedirectResponse Redirect to welcome page
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

        $this->sendWelcomeNotification($user);

        $this->notifyAdminsOfNewUser($user);

        return redirect(route('guest.welcome', absolute: false));
    }

    /**
     * Send welcome notification to newly registered user.
     *
     * Sends an informational notification welcoming the user
     * and explaining their guest access status.
     *
     * @param  User  $user  The newly registered user
     * @return void
     */
    private function sendWelcomeNotification(User $user): void
    {
        try {
            $this->signalRegistry->dispatch('user.registered', [
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to send welcome notification', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Notify Super Admins when a new user registers.
     *
     * Sends notifications to all Super Admin users informing them
     * of the new registration.
     *
     * @param  User  $newUser  The newly registered user
     * @return void
     */
    private function notifyAdminsOfNewUser(User $newUser): void
    {
        try {
            $admins = User::whereHas('roles', function ($query) {
                $query->where('name', Roles::SUPER_ADMIN);
            })->get();

            foreach ($admins as $admin) {
                $this->signalRegistry->dispatch('user.registered.admin', [
                    'user' => $admin,
                    'new_user' => $newUser,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to notify admins of new user', ['error' => $e->getMessage()]);
        }
    }
}
