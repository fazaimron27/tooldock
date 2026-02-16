<?php

/**
 * Welcome Controller.
 *
 * Renders the public landing page or redirects
 * guest-only users to the guest welcome page.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Traits\ChecksGuestUser;

class WelcomeController extends Controller
{
    use ChecksGuestUser;

    /**
     * Display the welcome/landing page.
     *
     * Shows the landing page for unauthenticated users.
     * Redirects authenticated guest users to the guest welcome page.
     *
     * @return Response|RedirectResponse Inertia landing page or redirect
     */
    public function index(): Response|RedirectResponse
    {
        $user = request()->user();

        if ($user && $this->isGuestOnly($user)) {
            return redirect()->route('guest.welcome');
        }

        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
        ]);
    }
}
