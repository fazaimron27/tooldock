<?php

namespace App\Events\Modules;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Nwidart\Modules\Module;

/**
 * Event dispatched before a module is uninstalled.
 *
 * Listeners can prevent uninstallation by setting $preventUninstall to true
 * and optionally providing a $preventionReason.
 */
class ModuleUninstalling
{
    use Dispatchable, SerializesModels;

    /**
     * The module being uninstalled.
     */
    public Module $module;

    /**
     * The name of the module.
     */
    public string $moduleName;

    /**
     * Whether uninstallation should be prevented.
     */
    public bool $preventUninstall = false;

    /**
     * Optional reason for preventing uninstallation.
     */
    public ?string $preventionReason = null;

    /**
     * Create a new event instance.
     *
     * @param  Module  $module  The module being uninstalled
     * @param  string  $moduleName  The name of the module
     */
    public function __construct(Module $module, string $moduleName)
    {
        $this->module = $module;
        $this->moduleName = $moduleName;
    }
}
