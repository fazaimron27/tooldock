<?php

/**
 * Guest Controller.
 *
 * Renders the guest welcome page for users with
 * guest-only permissions and no module access.
 *
 * @author Tool Dock Team
 * @license MIT
 */

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
     *
     * @return Response Inertia guest welcome page response
     */
    public function index(): Response
    {
        return Inertia::render('Modules::Core/Guest/Welcome');
    }
}
