<?php

/**
 * Routine Auth Service Provider
 *
 * Registers model-to-policy mappings for the Routine module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Routine\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\Routine\Models\Habit;
use Modules\Routine\Policies\HabitPolicy;

/**
 * Class AuthServiceProvider
 *
 * Maps the Habit model to HabitPolicy for authorization checks.
 *
 * @see \Modules\Routine\Policies\HabitPolicy
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Habit::class => HabitPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
