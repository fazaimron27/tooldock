<?php

/**
 * Profile Controller
 *
 * Handles user profile management including viewing, editing,
 * and deleting user accounts. Integrates with AuditLog and Signal.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Modules\AuditLog\Enums\AuditLogEvent;
use Modules\AuditLog\Traits\DispatchAuditLog;
use Modules\Core\Http\Requests\ProfileUpdateRequest;
use Modules\Core\Models\User;
use Modules\Media\Models\MediaFile;
use Modules\Signal\Traits\SendsSignalNotifications;

/**
 * Class ProfileController
 *
 * Manages user profile operations including updates, avatar changes,
 * and account deletion. Logs all changes and sends security alerts.
 *
 * @see \Modules\AuditLog\Traits\DispatchAuditLog For audit logging
 * @see \Modules\Signal\Traits\SendsSignalNotifications For notifications
 */
class ProfileController extends Controller
{
    use DispatchAuditLog;
    use SendsSignalNotifications;

    /**
     * Display the user's profile form.
     *
     * Loads the profile edit page with current user data and avatar.
     *
     * @param  Request  $request  The HTTP request
     * @return Response Inertia profile edit page
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        if (! $user->relationLoaded('avatar')) {
            $user->load('avatar');
        }

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'avatar' => $user->avatar ? [
                'id' => $user->avatar->id,
                'url' => $user->avatar->url,
            ] : null,
        ]);
    }

    /**
     * Update the user's profile information.
     *
     * Updates name, email, and avatar. Logs changes appropriately
     * and sends security alerts for email changes.
     *
     * @param  ProfileUpdateRequest  $request  The validated profile request
     * @return RedirectResponse Redirect to profile with success message
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        $emailChanged = $user->isDirty('email');
        $dirtyFields = $user->getDirty();
        $original = $user->getOriginal();

        if ($emailChanged) {
            $user->email_verified_at = null;
            $dirtyFields = $user->getDirty();
        }

        $otherFieldsChanged = ! empty(array_diff_key($dirtyFields, ['email' => true, 'email_verified_at' => true]));

        /**
         * Disable automatic logging to prevent duplicate events.
         * We'll log custom events (email_changed, updated) instead.
         */
        if ($emailChanged || $otherFieldsChanged) {
            \Modules\Core\Models\User::withoutLoggingActivity(function () use ($user) {
                $user->save();
            });
        } else {
            $user->save();
        }

        $this->logEmailChange($request, $user, $emailChanged, $original['email'] ?? null);
        $this->logOtherFieldChanges($request, $user, $otherFieldsChanged, $dirtyFields, $original);
        $this->handleAvatarChange($request, $user);

        return Redirect::route('profile.edit')->with('success', 'Profile updated successfully.');
    }

    /**
     * Log email change event.
     *
     * Creates an audit log entry and sends a security alert
     * when the user's email address is changed.
     *
     * @param  Request  $request  The HTTP request
     * @param  User  $user  The user model
     * @param  bool  $emailChanged  Whether email was changed
     * @param  string|null  $oldEmail  The previous email address
     * @return void
     */
    private function logEmailChange(Request $request, User $user, bool $emailChanged, ?string $oldEmail): void
    {
        if (! $emailChanged) {
            return;
        }

        $this->dispatchAuditLog(
            event: AuditLogEvent::EMAIL_CHANGED,
            model: $user,
            oldValues: [
                'email' => $oldEmail,
            ],
            newValues: [
                'email' => $user->email,
                'changed_at' => now()->toIso8601String(),
            ],
            tags: 'profile,email_change',
            request: $request,
            userId: $user->id
        );

        $this->signalAlert(
            $user,
            'Email Address Changed',
            "Your email was changed from {$oldEmail} to {$user->email}. Email verification is required. If you did not make this change, please contact support immediately.",
            route('profile.edit'),
            'System',
            'security'
        );
    }

    /**
     * Log other field changes (like name) as updated event.
     *
     * Creates an audit log entry for non-email field changes.
     *
     * @param  Request  $request  The HTTP request
     * @param  User  $user  The user model
     * @param  bool  $otherFieldsChanged  Whether other fields changed
     * @param  array  $dirtyFields  Changed fields array
     * @param  array  $original  Original field values
     * @return void
     */
    private function logOtherFieldChanges(Request $request, User $user, bool $otherFieldsChanged, array $dirtyFields, array $original): void
    {
        if (! $otherFieldsChanged) {
            return;
        }

        $otherFieldsOldValues = [];
        $otherFieldsNewValues = [];

        foreach ($dirtyFields as $key => $value) {
            if (in_array($key, ['email', 'email_verified_at'], true)) {
                continue;
            }
            $otherFieldsOldValues[$key] = $original[$key] ?? null;
            $otherFieldsNewValues[$key] = $value;
        }

        if (empty($otherFieldsOldValues)) {
            return;
        }

        $this->dispatchAuditLog(
            event: AuditLogEvent::UPDATED,
            model: $user,
            oldValues: $otherFieldsOldValues,
            newValues: $otherFieldsNewValues,
            tags: 'profile,update',
            request: $request,
            userId: $user->id
        );
    }

    /**
     * Handle avatar changes (attach, update, or delete).
     *
     * Processes avatar file attachment or removal based on request.
     *
     * @param  Request  $request  The HTTP request
     * @param  User  $user  The user model
     * @return void
     */
    private function handleAvatarChange(Request $request, User $user): void
    {
        if (! $request->has('avatar_id')) {
            return;
        }

        $avatarId = $request->input('avatar_id');
        $avatarId = ($avatarId === '' || $avatarId === null) ? null : $avatarId;

        if (! $user->relationLoaded('avatar')) {
            $user->load('avatar');
        }

        /**
         * Handle avatar deletion or replacement.
         * Removes current avatar if empty ID provided.
         * Replaces existing avatar if a different one is provided.
         */
        if (empty($avatarId)) {
            if ($user->avatar) {
                $user->avatar->delete();
            }

            return;
        }

        if ($user->avatar && $avatarId != $user->avatar->id) {
            $user->avatar->delete();
        }

        $avatar = MediaFile::find($avatarId);
        if ($avatar) {
            $avatar->update([
                'model_type' => $user::class,
                'model_id' => $user->id,
                'is_temporary' => false,
            ]);
        }
    }

    /**
     * Delete the user's account.
     *
     * Permanently deletes the user account after password verification.
     * Logs the deletion to audit log before removing the user.
     *
     * @param  Request  $request  The HTTP request with password confirmation
     * @return RedirectResponse|\Illuminate\Http\Response Redirect to home
     */
    public function destroy(Request $request): RedirectResponse|\Illuminate\Http\Response
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ], [], [
            'password' => 'password',
        ]);

        $user = $request->user();
        $userId = $user->id;
        $userEmail = $user->email;
        $userName = $user->name;
        $url = $request->url();
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        /**
         * Log account deletion synchronously before user deletion.
         * This ensures user_id is preserved in the audit log.
         */
        \Modules\AuditLog\Models\AuditLog::createDirect(
            event: AuditLogEvent::ACCOUNT_DELETED,
            model: $user,
            oldValues: [
                'email' => $userEmail,
                'name' => $userName,
                'deleted_at' => now()->toIso8601String(),
            ],
            newValues: null,
            userId: $userId,
            url: $url,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            tags: 'profile,account_deletion'
        );

        Auth::logout();

        /**
         * Disable automatic logging to prevent duplicate deleted event.
         * Account deletion is already logged above.
         */
        \Modules\Core\Models\User::withoutLoggingActivity(function () use ($user) {
            $user->delete();
        });

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        /**
         * Force full page reload for Inertia requests after session invalidation.
         * Flash messages cannot be used since session is already invalidated.
         */
        if ($request->header('X-Inertia')) {
            return response('', 409)
                ->header('X-Inertia-Location', url('/'));
        }

        return Redirect::to('/')->with('success', 'Your account has been permanently deleted.');
    }
}
