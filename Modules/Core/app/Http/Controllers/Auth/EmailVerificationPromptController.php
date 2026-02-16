<?php

/**
 * Email Verification Prompt Controller.
 *
 * Displays the email verification prompt page or redirects
 * already verified users to the dashboard.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Core\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     *
     * Redirects verified users to the dashboard, or shows the
     * verification prompt page for unverified users.
     *
     * @param  Request  $request  The HTTP request
     * @return RedirectResponse|Response Redirect or Inertia verification page
     */
    public function __invoke(Request $request): RedirectResponse|Response
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->intended(route('dashboard', absolute: false))
            : Inertia::render('Modules::Core/Auth/VerifyEmail', ['status' => session('status')]);
    }
}
