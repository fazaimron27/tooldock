<?php

/**
 * Hook Auth Service Provider
 *
 * Registers model-to-policy mappings for the Hook module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Hook\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\Hook\Models\HookInbound;
use Modules\Hook\Models\HookOutbound;
use Modules\Hook\Policies\HookInboundPolicy;
use Modules\Hook\Policies\HookOutboundPolicy;

/**
 * Class AuthServiceProvider
 *
 * Maps Hook models to their authorization policies.
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected $policies = [
        HookInbound::class => HookInboundPolicy::class,
        HookOutbound::class => HookOutboundPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
