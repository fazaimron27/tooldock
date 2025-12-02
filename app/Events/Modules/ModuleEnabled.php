<?php

namespace App\Events\Modules;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Nwidart\Modules\Module;

/**
 * Event dispatched after a module has been successfully enabled.
 *
 * This event is deferred in HTTP context for better performance,
 * but dispatched synchronously in CLI context for easier debugging.
 */
class ModuleEnabled
{
    use Dispatchable, SerializesModels;

    /**
     * The module that was enabled.
     */
    public Module $module;

    /**
     * The name of the module.
     */
    public string $moduleName;

    /**
     * Create a new event instance.
     *
     * @param  Module  $module  The module that was enabled
     * @param  string  $moduleName  The name of the module
     */
    public function __construct(Module $module, string $moduleName)
    {
        $this->module = $module;
        $this->moduleName = $moduleName;
    }
}
