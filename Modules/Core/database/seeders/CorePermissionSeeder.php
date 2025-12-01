<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\App\Constants\Roles as RoleConstants;
use Modules\Core\App\Models\User;
use Modules\Core\App\Services\PermissionRegistry;
use Spatie\Permission\Models\Role;

class CorePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::firstOrCreate(['name' => RoleConstants::SUPER_ADMIN]);
        Role::firstOrCreate(['name' => RoleConstants::ADMINISTRATOR]);
        Role::firstOrCreate(['name' => RoleConstants::MANAGER]);
        Role::firstOrCreate(['name' => RoleConstants::STAFF]);
        Role::firstOrCreate(['name' => RoleConstants::AUDITOR]);

        app(PermissionRegistry::class)->register('core', [
            'dashboard.view',
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'roles.manage',
        ], [
            RoleConstants::ADMINISTRATOR => [
                'dashboard.view',
                'users.view',
                'users.create',
                'users.edit',
                'users.delete',
                'roles.manage',
            ],
            RoleConstants::MANAGER => [
                'dashboard.view',
                'users.view',
            ],
            RoleConstants::STAFF => [
                'dashboard.view',
            ],
            RoleConstants::AUDITOR => [
                'dashboard.view',
                'users.view',
            ],
        ]);

        $superAdminEmail = config('core.super_admin_email', 'superadmin@example.com');
        $superAdminPassword = config('core.super_admin_password', 'password');

        if ($superAdminEmail && $superAdminPassword) {
            $superAdminUser = User::withoutEvents(function () use ($superAdminEmail, $superAdminPassword) {
                return User::firstOrCreate(
                    ['email' => $superAdminEmail],
                    array_merge(
                        User::factory()->make()->getAttributes(),
                        [
                            'name' => 'Super Administrator',
                            'email' => $superAdminEmail,
                            'password' => $superAdminPassword,
                            'email_verified_at' => now(),
                        ]
                    )
                );
            });

            if (! $superAdminUser->hasRole(RoleConstants::SUPER_ADMIN)) {
                $superAdminUser->assignRole(RoleConstants::SUPER_ADMIN);
            }
        }
    }
}
