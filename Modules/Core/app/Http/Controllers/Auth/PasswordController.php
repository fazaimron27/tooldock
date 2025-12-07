<?php

namespace Modules\Core\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Modules\AuditLog\Enums\AuditLogEvent;
use Modules\AuditLog\Traits\DispatchAuditLog;

class PasswordController extends Controller
{
    use DispatchAuditLog;

    /**
     * Update the user's password.
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

        return back()->with('success', 'Password updated successfully.');
    }
}
