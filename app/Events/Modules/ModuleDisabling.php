<?php

namespace App\Events\Modules;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Nwidart\Modules\Module;

/**
 * Event dispatched before a module is disabled.
 *
 * Listeners can prevent disabling by setting $preventDisable to true
 * and optionally providing a $preventionReason.
 */
class ModuleDisabling
{
    use Dispatchable, SerializesModels;

    /**
     * The module being disabled.
     */
    public Module $module;

    /**
     * The name of the module.
     */
    public string $moduleName;

    /**
     * Whether disabling should be prevented.
     */
    public bool $preventDisable = false;

    /**
     * Optional reason for preventing disabling.
     */
    public ?string $preventionReason = null;

    /**
     * Create a new event instance.
     *
     * @param  Module  $module  The module being disabled
     * @param  string  $moduleName  The name of the module
     */
    public function __construct(Module $module, string $moduleName)
    {
        $this->module = $module;
        $this->moduleName = $moduleName;
    }
}
