<?php

namespace App\Events\Modules;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Nwidart\Modules\Module;

/**
 * Event dispatched before a module is installed.
 *
 * Listeners can prevent installation by setting $preventInstall to true
 * and optionally providing a $preventionReason.
 */
class ModuleInstalling
{
    use Dispatchable, SerializesModels;

    /**
     * The module being installed.
     */
    public Module $module;

    /**
     * The name of the module.
     */
    public string $moduleName;

    /**
     * Whether to run seeders during installation.
     */
    public bool $withSeed;

    /**
     * Whether to skip dependency validation.
     */
    public bool $skipValidation;

    /**
     * Whether installation should be prevented.
     */
    public bool $preventInstall = false;

    /**
     * Optional reason for preventing installation.
     */
    public ?string $preventionReason = null;

    /**
     * Create a new event instance.
     *
     * @param  Module  $module  The module being installed
     * @param  string  $moduleName  The name of the module
     * @param  bool  $withSeed  Whether to run seeders during installation
     * @param  bool  $skipValidation  Whether to skip dependency validation
     */
    public function __construct(Module $module, string $moduleName, bool $withSeed = false, bool $skipValidation = false)
    {
        $this->module = $module;
        $this->moduleName = $moduleName;
        $this->withSeed = $withSeed;
        $this->skipValidation = $skipValidation;
    }
}
