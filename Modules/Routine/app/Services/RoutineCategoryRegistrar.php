<?php

/**
 * Routine Category Registrar
 *
 * Registers default categories for the Routine module
 * to classify habits into meaningful groups.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Routine\Services;

use App\Services\Registry\CategoryRegistry;

/**
 * Handles category registration for the Routine module.
 *
 * Provides a set of default habit categories that users can assign
 * to their habits for better organisation and filtering.
 */
class RoutineCategoryRegistrar
{
    /**
     * Register default categories for the Routine module.
     *
     * @param  CategoryRegistry  $categoryRegistry
     * @param  string  $moduleName
     * @return void
     */
    public function register(CategoryRegistry $categoryRegistry, string $moduleName): void
    {
        $categoryRegistry->registerMany($moduleName, 'habit_category', [
            ['name' => 'Health & Fitness', 'slug' => 'health-fitness', 'color' => '#10b981', 'description' => 'Exercise, hydration, sleep habits'],
            ['name' => 'Learning & Growth', 'slug' => 'learning-growth', 'color' => '#3b82f6', 'description' => 'Reading, studying, skill-building'],
            ['name' => 'Mindfulness', 'slug' => 'mindfulness', 'color' => '#8b5cf6', 'description' => 'Meditation, journaling, gratitude'],
            ['name' => 'Productivity', 'slug' => 'productivity', 'color' => '#f59e0b', 'description' => 'Focus, planning, work habits'],
            ['name' => 'Self-Care', 'slug' => 'self-care', 'color' => '#ec4899', 'description' => 'Skincare, relaxation, mental health'],
            ['name' => 'Social', 'slug' => 'social', 'color' => '#06b6d4', 'description' => 'Family, friends, communication'],
            ['name' => 'Finance', 'slug' => 'finance', 'color' => '#6366f1', 'description' => 'Budgeting, saving habits'],
            ['name' => 'Other', 'slug' => 'other', 'color' => '#6b7280', 'description' => 'Uncategorized habits'],
        ]);
    }
}
