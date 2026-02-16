<?php

/**
 * Confirmable Password Controller.
 *
 * Handles password confirmation for sensitive actions,
 * displaying the confirmation form and validating the password.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Core\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ConfirmablePasswordController extends Controller
{
    /**
     * Show the confirm password view.
     *
     * @return Response Inertia confirm password page response
     */
    public function show(): Response
    {
        return Inertia::render('Modules::Core/Auth/ConfirmPassword');
    }

    /**
     * Confirm the user's password.
     *
     * @param  Request  $request  The HTTP request containing the password
     * @return RedirectResponse Redirect to intended destination
     *
     * @throws ValidationException If password validation fails
     */
    public function store(Request $request): RedirectResponse
    {
        if (! Auth::guard('web')->validate([
            'email' => $request->user()->email,
            'password' => $request->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
