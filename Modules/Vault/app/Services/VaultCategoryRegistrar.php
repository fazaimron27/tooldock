<?php

/**
 * Vault Category Registrar
 *
 * Registers default categories for the Vault module dropdown.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Vault\Services;

use App\Services\Registry\CategoryRegistry;

/**
 * Class VaultCategoryRegistrar
 *
 * Provides default vault categories (Banking, Credit Cards, Email, etc.)
 * for the category dropdown in the vault creation/edit forms.
 *
 * @see \App\Services\Registry\CategoryRegistry
 */
class VaultCategoryRegistrar
{
    /**
     * Register default categories for the Vault module.
     *
     * @param  CategoryRegistry  $registry  The central category registry
     * @param  string  $moduleName  The module name identifier
     * @return void
     */
    public function register(CategoryRegistry $registry, string $moduleName): void
    {
        $registry->registerMany($moduleName, 'vault', [
            [
                'name' => 'Banking',
                'slug' => 'banking',
                'color' => '#10B981',
                'description' => 'Banking and financial institution credentials',
            ],
            [
                'name' => 'Credit Cards',
                'slug' => 'credit-cards',
                'color' => '#F59E0B',
                'description' => 'Credit card information and credentials',
            ],
            [
                'name' => 'Email Accounts',
                'slug' => 'email-accounts',
                'color' => '#3B82F6',
                'description' => 'Email service provider accounts',
            ],
            [
                'name' => 'Social Media',
                'slug' => 'social-media',
                'color' => '#EC4899',
                'description' => 'Social media platform accounts and passwords',
            ],
            [
                'name' => 'Cloud Storage',
                'slug' => 'cloud-storage',
                'color' => '#8B5CF6',
                'description' => 'Cloud storage service credentials',
            ],
            [
                'name' => 'E-commerce',
                'slug' => 'ecommerce',
                'color' => '#EF4444',
                'description' => 'Online shopping and marketplace accounts',
            ],
            [
                'name' => 'Work Accounts',
                'slug' => 'work-accounts',
                'color' => '#06B6D4',
                'description' => 'Work-related accounts and credentials',
            ],
            [
                'name' => 'Server Access',
                'slug' => 'server-access',
                'color' => '#14B8A6',
                'description' => 'Server and infrastructure access credentials',
            ],
            [
                'name' => 'Database',
                'slug' => 'database',
                'color' => '#F97316',
                'description' => 'Database connection credentials',
            ],
            [
                'name' => 'API Keys',
                'slug' => 'api-keys',
                'color' => '#84CC16',
                'description' => 'API keys and authentication tokens',
            ],
            [
                'name' => 'Personal Notes',
                'slug' => 'personal-notes',
                'color' => '#64748B',
                'description' => 'Personal secure notes and information',
            ],
        ]);
    }
}
