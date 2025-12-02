<?php

namespace App\Events\Modules;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Nwidart\Modules\Module;

/**
 * Event dispatched after a module has been successfully installed.
 *
 * This event is deferred in HTTP context for better performance,
 * but dispatched synchronously in CLI context for easier debugging.
 */
class ModuleInstalled
{
    use Dispatchable, SerializesModels;

    /**
     * The module that was installed.
     */
    public Module $module;

    /**
     * The name of the module.
     */
    public string $moduleName;

    /**
     * Whether seeders were run during installation.
     */
    public bool $withSeed;

    /**
     * Create a new event instance.
     *
     * @param  Module  $module  The module that was installed
     * @param  string  $moduleName  The name of the module
     * @param  bool  $withSeed  Whether seeders were run during installation
     */
    public function __construct(Module $module, string $moduleName, bool $withSeed = false)
    {
        $this->module = $module;
        $this->moduleName = $moduleName;
        $this->withSeed = $withSeed;
    }
}
