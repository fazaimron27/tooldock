<?php

/**
 * Auth Service Provider.
 *
 * Registers model-policy mappings for the Settings module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Settings\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\Settings\Models\Setting;
use Modules\Settings\Policies\SettingPolicy;

/**
 * Class AuthServiceProvider
 *
 * Maps the Setting model to SettingPolicy for authorization checks.
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Setting::class => SettingPolicy::class,
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
