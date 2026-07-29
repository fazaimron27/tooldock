<?php

namespace Modules\Sandbox\Services;

use App\Services\Registry\MenuRegistry;

class SandboxMenuRegistrar
{
    public function register(MenuRegistry $menuRegistry, string $moduleName): void
    {
        $menuRegistry->registerItem(
            group: 'Developer',
            label: 'Sandbox',
            route: 'sandbox.index',
            icon: 'FlaskConical',
            order: 30,
            permission: 'sandbox.intake.view',
            parentKey: null,
            module: $moduleName
        );
    }
}
