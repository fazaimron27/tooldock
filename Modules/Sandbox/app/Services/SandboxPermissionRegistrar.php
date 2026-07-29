<?php

namespace Modules\Sandbox\Services;

use App\Services\Registry\PermissionRegistry;
use Modules\Core\Constants\Roles as RoleConstants;

class SandboxPermissionRegistrar
{
    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->register('sandbox', [
            'intake.view',
        ], [
            RoleConstants::ADMINISTRATOR => [
                'intake.view',
            ],
        ]);
    }
}
