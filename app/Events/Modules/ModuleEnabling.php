<?php

namespace App\Events\Modules;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Nwidart\Modules\Module;

/**
 * Event dispatched before a module is enabled.
 *
 * Listeners can prevent enabling by setting $preventEnable to true
 * and optionally providing a $preventionReason.
 */
class ModuleEnabling
{
    use Dispatchable, SerializesModels;

    /**
     * The module being enabled.
     */
    public Module $module;

    /**
     * The name of the module.
     */
    public string $moduleName;

    /**
     * Whether to skip dependency validation.
     */
    public bool $skipValidation;

    /**
     * Whether enabling should be prevented.
     */
    public bool $preventEnable = false;

    /**
     * Optional reason for preventing enabling.
     */
    public ?string $preventionReason = null;

    /**
     * Create a new event instance.
     *
     * @param  Module  $module  The module being enabled
     * @param  string  $moduleName  The name of the module
     * @param  bool  $skipValidation  Whether to skip dependency validation
     */
    public function __construct(Module $module, string $moduleName, bool $skipValidation = false)
    {
        $this->module = $module;
        $this->moduleName = $moduleName;
        $this->skipValidation = $skipValidation;
    }
}
