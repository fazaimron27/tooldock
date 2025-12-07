<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\App\Traits\ChecksGuestUser;

class WelcomeController extends Controller
{
    use ChecksGuestUser;

    /**
     * Display the welcome/landing page.
     *
     * Shows the landing page for unauthenticated users.
     * Redirects authenticated guest users to the guest welcome page.
     */
    public function index(): Response|RedirectResponse
    {
        $user = request()->user();

        // If user is authenticated and is a guest-only user, redirect to guest welcome page
        if ($user && $this->isGuestOnly($user)) {
            return redirect()->route('guest.welcome');
        }

        // Show landing page for unauthenticated users or authenticated non-guest users
        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
        ]);
    }
}
