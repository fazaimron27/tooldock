<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
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
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Core Permissions
        $permissions = [
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

        // Update cache to know about the newly created permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create 5 Standard Roles
        // 1. Super Admin - Grant all via Gate::before() (no permissions assigned)
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);

        // 2. Administrator - Assign manage users, manage roles
        $administratorRole = Role::firstOrCreate(['name' => 'Administrator']);
        $administratorRole->syncPermissions([
            'view users',
            'create users',
            'edit users',
            'delete users',
            'manage roles',
        ]);

        // 3. Manager - Assign view users
        $managerRole = Role::firstOrCreate(['name' => 'Manager']);
        $managerRole->syncPermissions(['view users']);

        // 4. Staff - Empty initially
        $staffRole = Role::firstOrCreate(['name' => 'Staff']);

        // 5. Auditor - Assign view users
        $auditorRole = Role::firstOrCreate(['name' => 'Auditor']);
        $auditorRole->syncPermissions(['view users']);

        // Create Default Super Admin User (if not exists)
        $adminEmail = env('ADMIN_EMAIL', 'admin@example.com');
        $adminPassword = env('ADMIN_PASSWORD', 'password');

        $adminUser = User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make($adminPassword),
                'email_verified_at' => now(),
            ]
        );

        // Assign Super Admin role if not already assigned
        if (! $adminUser->hasRole('Super Admin')) {
            $adminUser->assignRole('Super Admin');
        }
    }
}
