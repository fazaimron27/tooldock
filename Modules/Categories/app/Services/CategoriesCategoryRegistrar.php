<?php

namespace Modules\Categories\Services;

use App\Services\Registry\CategoryRegistry;

/**
 * Handles default category registration for the Categories module.
 *
 * These are sample categories for development/testing purposes.
 * Other modules can register their own categories using CategoryRegistry.
 */
class CategoriesCategoryRegistrar
{
    /**
     * Register default categories for the Categories module.
     */
    public function register(CategoryRegistry $registry, string $moduleName): void
    {
        $registry->registerMany($moduleName, 'product', [
            [
                'name' => 'Electronics',
                'slug' => 'electronics',
                'color' => '#3B82F6',
                'description' => 'Electronic devices and components',
            ],
            [
                'name' => 'Smartphones',
                'slug' => 'smartphones',
                'parent_slug' => 'electronics',
                'color' => '#2563EB',
                'description' => 'Mobile phones and smartphones',
            ],
            [
                'name' => 'Laptops',
                'slug' => 'laptops',
                'parent_slug' => 'electronics',
                'color' => '#1D4ED8',
                'description' => 'Laptop computers and accessories',
            ],
            [
                'name' => 'Clothing',
                'slug' => 'clothing',
                'color' => '#EC4899',
                'description' => 'Apparel and fashion items',
            ],
            [
                'name' => 'Men\'s Wear',
                'slug' => 'mens-wear',
                'parent_slug' => 'clothing',
                'color' => '#DB2777',
                'description' => 'Men\'s clothing and accessories',
            ],
            [
                'name' => 'Women\'s Wear',
                'slug' => 'womens-wear',
                'parent_slug' => 'clothing',
                'color' => '#BE185D',
                'description' => 'Women\'s clothing and accessories',
            ],
        ]);

        $registry->registerMany($moduleName, 'finance', [
            [
                'name' => 'Income',
                'slug' => 'income',
                'color' => '#10B981',
                'description' => 'Revenue and income sources',
            ],
            [
                'name' => 'Sales Revenue',
                'slug' => 'sales-revenue',
                'parent_slug' => 'income',
                'color' => '#059669',
                'description' => 'Revenue from product sales',
            ],
            [
                'name' => 'Service Revenue',
                'slug' => 'service-revenue',
                'parent_slug' => 'income',
                'color' => '#047857',
                'description' => 'Revenue from services provided',
            ],
            [
                'name' => 'Operating Expenses',
                'slug' => 'operating-expenses',
                'color' => '#EF4444',
                'description' => 'Day-to-day business expenses',
            ],
            [
                'name' => 'Salaries',
                'slug' => 'salaries',
                'parent_slug' => 'operating-expenses',
                'color' => '#DC2626',
                'description' => 'Employee salaries and wages',
            ],
            [
                'name' => 'Utilities',
                'slug' => 'utilities',
                'parent_slug' => 'operating-expenses',
                'color' => '#B91C1C',
                'description' => 'Electricity, water, internet, etc.',
            ],
            [
                'name' => 'Marketing',
                'slug' => 'marketing',
                'parent_slug' => 'operating-expenses',
                'color' => '#991B1B',
                'description' => 'Marketing and advertising expenses',
            ],
        ]);

        $registry->registerMany($moduleName, 'project', [
            [
                'name' => 'Web Development',
                'slug' => 'web-development',
                'color' => '#8B5CF6',
                'description' => 'Website and web application projects',
            ],
            [
                'name' => 'Mobile App',
                'slug' => 'mobile-app',
                'color' => '#7C3AED',
                'description' => 'Mobile application development projects',
            ],
            [
                'name' => 'Infrastructure',
                'slug' => 'infrastructure',
                'color' => '#6D28D9',
                'description' => 'IT infrastructure and system projects',
            ],
        ]);

        $registry->registerMany($moduleName, 'inventory', [
            [
                'name' => 'Raw Materials',
                'slug' => 'raw-materials',
                'color' => '#F59E0B',
                'description' => 'Raw materials and components',
            ],
            [
                'name' => 'Finished Goods',
                'slug' => 'finished-goods',
                'color' => '#D97706',
                'description' => 'Completed products ready for sale',
            ],
            [
                'name' => 'Work in Progress',
                'slug' => 'work-in-progress',
                'color' => '#B45309',
                'description' => 'Items currently in production',
            ],
        ]);

        $registry->registerMany($moduleName, 'expense', [
            [
                'name' => 'Office Supplies',
                'slug' => 'office-supplies',
                'color' => '#06B6D4',
                'description' => 'Office equipment and supplies',
            ],
            [
                'name' => 'Travel',
                'slug' => 'travel',
                'color' => '#0891B2',
                'description' => 'Business travel expenses',
            ],
            [
                'name' => 'Training',
                'slug' => 'training',
                'color' => '#0E7490',
                'description' => 'Employee training and development',
            ],
        ]);

        $registry->registerMany($moduleName, 'department', [
            [
                'name' => 'Sales',
                'slug' => 'sales',
                'color' => '#14B8A6',
                'description' => 'Sales department',
            ],
            [
                'name' => 'Marketing',
                'slug' => 'marketing',
                'color' => '#0D9488',
                'description' => 'Marketing department',
            ],
            [
                'name' => 'IT',
                'slug' => 'it',
                'color' => '#0F766E',
                'description' => 'Information Technology department',
            ],
            [
                'name' => 'HR',
                'slug' => 'hr',
                'color' => '#115E59',
                'description' => 'Human Resources department',
            ],
            [
                'name' => 'Finance',
                'slug' => 'finance',
                'color' => '#134E4A',
                'description' => 'Finance and accounting department',
            ],
        ]);
    }
}
