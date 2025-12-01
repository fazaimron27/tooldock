<?php

namespace Modules\AuditLog\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\App\Constants\Roles;
use Modules\Core\App\Services\PermissionRegistry;

class AuditLogPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistry::class)->register('auditlog', [
            'view',
        ], [
            Roles::ADMINISTRATOR => ['view'],
            Roles::MANAGER => ['view'],
            Roles::AUDITOR => ['view'],
        ]);
    }
}
