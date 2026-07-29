<?php

/**
 * Bot Auth Service Provider
 *
 * Registers model-to-policy mappings for the Bot module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Bot\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\Bot\Models\BotPlatform;
use Modules\Bot\Policies\BotPlatformPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected $policies = [
        BotPlatform::class => BotPlatformPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
