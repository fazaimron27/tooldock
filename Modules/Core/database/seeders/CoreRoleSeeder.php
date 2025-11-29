<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Core\App\Constants\Roles as RoleConstants;
use Modules\Core\App\Models\User;
use Modules\Core\App\Services\PermissionCacheService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CoreRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'core.dashboard.view',
            'core.users.view',
            'core.users.create',
            'core.users.edit',
            'core.users.delete',
            'core.roles.manage',
            'core.settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superAdminRole = Role::firstOrCreate(['name' => RoleConstants::SUPER_ADMIN]);

        $administratorRole = Role::firstOrCreate(['name' => RoleConstants::ADMINISTRATOR]);
        $administratorRole->syncPermissions([
            'core.dashboard.view',
            'core.users.view',
            'core.users.create',
            'core.users.edit',
            'core.users.delete',
            'core.roles.manage',
        ]);

        $managerRole = Role::firstOrCreate(['name' => RoleConstants::MANAGER]);
        $managerRole->syncPermissions([
            'core.dashboard.view',
            'core.users.view',
        ]);

        $staffRole = Role::firstOrCreate(['name' => RoleConstants::STAFF]);
        $staffRole->syncPermissions(['core.dashboard.view']);

        $auditorRole = Role::firstOrCreate(['name' => RoleConstants::AUDITOR]);
        $auditorRole->syncPermissions([
            'core.dashboard.view',
            'core.users.view',
        ]);

        $adminEmail = env('ADMIN_EMAIL', 'admin@example.com');
        $adminPassword = env('ADMIN_PASSWORD', 'password');

        if ($adminEmail && $adminPassword) {
            $adminUser = User::firstOrCreate(
                ['email' => $adminEmail],
                [
                    'name' => 'Super Administrator',
                    'password' => Hash::make($adminPassword),
                    'email_verified_at' => now(),
                ]
            );

            if (! $adminUser->hasRole(RoleConstants::SUPER_ADMIN)) {
                $adminUser->assignRole(RoleConstants::SUPER_ADMIN);
            }
        }

        app(PermissionCacheService::class)->clear();
    }
}
