<?php

/**
 * Base Controller.
 *
 * Abstract base controller for all application HTTP controllers.
 * Provides authorization capabilities via the AuthorizesRequests trait.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Base HTTP controller for the application.
 *
 * All application and module controllers should extend this class
 * to inherit authorization and other shared functionality.
 */
abstract class Controller
{
    use AuthorizesRequests;
}
