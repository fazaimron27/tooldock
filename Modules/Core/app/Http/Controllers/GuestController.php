<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class GuestController extends Controller
{
    /**
     * Display the guest welcome page.
     *
     * This page is shown to users who are in the Guest group and have no permissions.
     * It provides information about their account status and next steps.
     */
    public function index(): Response
    {
        return Inertia::render('Modules::Core/Guest/Welcome');
    }
}
