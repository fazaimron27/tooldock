<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Http\Requests\ProfileUpdateRequest;
use Modules\Media\Models\MediaFile;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $user->load('avatar');

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
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($request->has('avatar_id')) {
            $avatarId = $request->input('avatar_id');

            $user->load('avatar');

            if ($user->avatar) {
                $oldAvatarId = $user->avatar->id;
                if ($avatarId != $oldAvatarId || ! $avatarId) {
                    $user->avatar->delete();
                }
            }

            if ($avatarId) {
                $avatar = MediaFile::find($avatarId);
                if ($avatar) {
                    $avatar->update([
                        'model_type' => $user::class,
                        'model_id' => $user->id,
                        'is_temporary' => false,
                    ]);
                }
            }
        }

        return Redirect::route('profile.edit')->with('success', 'Profile updated successfully.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse|\Illuminate\Http\Response
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ], [], [
            'password' => 'password',
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        /**
         * For Inertia requests, return 409 with X-Inertia-Location header.
         * This forces a full page reload, which is necessary after session invalidation.
         * Note: Flash messages cannot be used here since the session is invalidated.
         */
        if ($request->header('X-Inertia')) {
            return response('', 409)
                ->header('X-Inertia-Location', url('/'));
        }

        return Redirect::to('/')->with('success', 'Your account has been permanently deleted.');
    }
}
