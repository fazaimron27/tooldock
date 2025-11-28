<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Core\App\Constants\Roles as RoleConstants;
use Modules\Core\App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CoreRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view dashboard',
            'view users',
            'create users',
            'edit users',
            'delete users',
            'manage roles',
            'manage settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $superAdminRole = Role::firstOrCreate(['name' => RoleConstants::SUPER_ADMIN]);

        $administratorRole = Role::firstOrCreate(['name' => RoleConstants::ADMINISTRATOR]);
        $administratorRole->syncPermissions([
            'view dashboard',
            'view users',
            'create users',
            'edit users',
            'delete users',
            'manage roles',
        ]);

        $managerRole = Role::firstOrCreate(['name' => RoleConstants::MANAGER]);
        $managerRole->syncPermissions([
            'view dashboard',
            'view users',
        ]);

        $staffRole = Role::firstOrCreate(['name' => RoleConstants::STAFF]);
        $staffRole->syncPermissions(['view dashboard']);

        $auditorRole = Role::firstOrCreate(['name' => RoleConstants::AUDITOR]);
        $auditorRole->syncPermissions([
            'view dashboard',
            'view users',
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
    }
}
