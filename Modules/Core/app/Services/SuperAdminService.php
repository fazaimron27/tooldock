<?php

namespace Modules\Core\App\Services;

use App\Services\Registry\RoleRegistry;
use Illuminate\Support\Facades\Log;
use Modules\Core\App\Constants\Roles;
use Modules\Core\App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Service for managing super admin user creation.
 *
 * Provides a centralized way to ensure the super admin user exists,
 * used by both CoreServiceProvider (during boot) and CorePermissionSeeder (as fallback).
 */
class SuperAdminService
{
    /**
     * Ensure super admin user exists.
     *
     * Creates the super admin user if it doesn't exist, using credentials from config.
     * This is idempotent - safe to call multiple times.
     *
     * @param  RoleRegistry  $roleRegistry  Registry for role management
     * @param  bool  $throwOnError  If true, exceptions will be thrown. If false (default), errors are logged.
     * @return bool True if user was created or already exists, false on error
     */
    public function ensureExists(RoleRegistry $roleRegistry, bool $throwOnError = false): bool
    {
        try {
            $superAdminEmail = config('core.super_admin_email', 'superadmin@example.com');
            $superAdminPassword = config('core.super_admin_password', 'password');

            if (! $superAdminEmail || ! $superAdminPassword) {
                Log::debug('SuperAdminService: Super admin credentials not configured', [
                    'email_configured' => ! empty($superAdminEmail),
                    'password_configured' => ! empty($superAdminPassword),
                ]);

                return false;
            }

            $existingUser = User::where('email', $superAdminEmail)->first();
            if ($existingUser) {
                if (! $existingUser->hasRole(Roles::SUPER_ADMIN)) {
                    $superAdminRole = $this->getOrCreateSuperAdminRole($roleRegistry);
                    if ($superAdminRole) {
                        $existingUser->assignRole($superAdminRole);
                        Log::info('SuperAdminService: Assigned Super Admin role to existing user', [
                            'user_id' => $existingUser->id,
                            'email' => $superAdminEmail,
                        ]);
                    }
                }

                return true;
            }

            $superAdminRole = $this->getOrCreateSuperAdminRole($roleRegistry);
            if (! $superAdminRole) {
                Log::error('SuperAdminService: Failed to get or create Super Admin role');
                if ($throwOnError) {
                    throw new \RuntimeException('Failed to get or create Super Admin role');
                }

                return false;
            }

            $superAdminUser = User::withoutEvents(function () use ($superAdminEmail, $superAdminPassword) {
                return User::create([
                    'name' => 'Super Administrator',
                    'email' => $superAdminEmail,
                    'password' => $superAdminPassword,
                    'email_verified_at' => now(),
                ]);
            });

            if (! $superAdminUser->hasRole(Roles::SUPER_ADMIN)) {
                $superAdminUser->assignRole($superAdminRole);
            }

            Log::info('SuperAdminService: Created super admin user', [
                'user_id' => $superAdminUser->id,
                'email' => $superAdminEmail,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('SuperAdminService: Failed to ensure super admin exists', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($throwOnError) {
                throw $e;
            }

            return false;
        }
    }

    /**
     * Get or create the Super Admin role.
     *
     * @param  RoleRegistry  $roleRegistry  Registry for role management
     * @return Role|null The Super Admin role, or null on failure
     */
    private function getOrCreateSuperAdminRole(RoleRegistry $roleRegistry): ?Role
    {
        $role = $roleRegistry->getRole(Roles::SUPER_ADMIN);

        if ($role) {
            return $role;
        }

        try {
            $role = Role::firstOrCreate(
                ['name' => Roles::SUPER_ADMIN],
                ['guard_name' => 'web']
            );

            Log::debug('SuperAdminService: Created Super Admin role (fallback)', [
                'role_id' => $role->id,
            ]);

            return $role;
        } catch (\Exception $e) {
            Log::error('SuperAdminService: Failed to create Super Admin role', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
