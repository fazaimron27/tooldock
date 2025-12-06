<?php

namespace App\Services\Registry;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Categories\Models\Category;

/**
 * Registry for managing default category registration.
 *
 * Allows modules to register their default categories during service provider boot.
 * Categories are automatically seeded into the database during module installation
 * and enabling via ModuleLifecycleService, similar to how settings are synced.
 *
 * Supports hierarchical categories with parent/child relationships.
 */
class CategoryRegistry
{
    /**
     * @var array<int, array{
     *     module: string,
     *     type: string,
     *     name: string,
     *     slug: string,
     *     parent_slug: string|null,
     *     color: string|null,
     *     description: string|null
     * }>
     */
    private array $categories = [];

    /**
     * Track registered categories by module and type to prevent duplicates.
     *
     * @var array<string, array<string, bool>> Format: ['module' => ['type:slug' => true]]
     */
    private array $registeredCategories = [];

    /**
     * Register a default category for a module.
     *
     * @param  string  $module  Module name (e.g., 'Blog', 'Newsletter')
     * @param  string  $type  Category type (e.g., 'product', 'finance', 'post')
     * @param  string  $name  Category name
     * @param  string|null  $slug  Category slug (auto-generated if null)
     * @param  string|null  $parentSlug  Parent category slug (for hierarchical categories)
     * @param  string|null  $color  Hex color code (e.g., '#FF0000')
     * @param  string|null  $description  Category description
     */
    public function register(
        string $module,
        string $type,
        string $name,
        ?string $slug = null,
        ?string $parentSlug = null,
        ?string $color = null,
        ?string $description = null
    ): void {
        $module = strtolower($module);
        $type = strtolower($type);
        $slug = $slug ?? Str::slug($name);
        $key = "{$type}:{$slug}";

        if (isset($this->registeredCategories[$module][$key])) {
            Log::warning('CategoryRegistry: Duplicate category registration', [
                'module' => $module,
                'type' => $type,
                'slug' => $slug,
            ]);

            return;
        }

        if (! isset($this->registeredCategories[$module])) {
            $this->registeredCategories[$module] = [];
        }
        $this->registeredCategories[$module][$key] = true;

        $this->categories[] = [
            'module' => $module,
            'type' => $type,
            'name' => $name,
            'slug' => $slug,
            'parent_slug' => $parentSlug,
            'color' => $color,
            'description' => $description,
        ];
    }

    /**
     * Register multiple categories at once.
     *
     * @param  string  $module  Module name
     * @param  string  $type  Category type
     * @param  array<int, array{name: string, slug?: string, parent_slug?: string, color?: string, description?: string}>  $categories  Array of category definitions
     */
    public function registerMany(string $module, string $type, array $categories): void
    {
        foreach ($categories as $category) {
            $this->register(
                module: $module,
                type: $type,
                name: $category['name'],
                slug: $category['slug'] ?? null,
                parentSlug: $category['parent_slug'] ?? null,
                color: $category['color'] ?? null,
                description: $category['description'] ?? null
            );
        }
    }

    /**
     * Get all registered categories.
     *
     * @return array<int, array{module: string, type: string, name: string, slug: string, parent_slug: string|null, color: string|null, description: string|null}>
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * Get categories for a specific module.
     *
     * @return array<int, array{module: string, type: string, name: string, slug: string, parent_slug: string|null, color: string|null, description: string|null}>
     */
    public function getCategoriesByModule(string $module): array
    {
        $module = strtolower($module);

        return array_filter($this->categories, fn ($category) => $category['module'] === $module);
    }

    /**
     * Get categories for a specific type.
     *
     * @return array<int, array{module: string, type: string, name: string, slug: string, parent_slug: string|null, color: string|null, description: string|null}>
     */
    public function getCategoriesByType(string $type): array
    {
        $type = strtolower($type);

        return array_filter($this->categories, fn ($category) => $category['type'] === $type);
    }

    /**
     * Seed all registered categories into the database.
     *
     * This is automatically called by ModuleLifecycleService during module installation
     * and enabling. Only creates categories that don't already exist (based on slug + type uniqueness).
     * Handles parent/child relationships automatically.
     *
     * Wrapped in a database transaction to ensure atomicity.
     *
     * @param  bool  $strict  If true, any exception during seeding will cause the transaction to rollback.
     *                        If false (default), exceptions are logged but processing continues.
     */
    public function seed(bool $strict = false): void
    {
        if (empty($this->categories)) {
            Log::debug('CategoryRegistry: No categories registered to seed');

            return;
        }

        Log::info('CategoryRegistry: Starting category seeding', [
            'total_categories' => count($this->categories),
        ]);

        DB::transaction(function () use ($strict) {
            $categoriesByType = [];
            foreach ($this->categories as $category) {
                $categoriesByType[$category['type']][] = $category;
            }

            $totalCreated = 0;
            $totalFound = 0;
            $totalErrors = 0;

            foreach ($categoriesByType as $type => $categories) {
                $stats = $this->seedCategoriesForType($type, $categories, $strict);
                $totalCreated += $stats['created'];
                $totalFound += $stats['found'];
                $totalErrors += $stats['errors'];
            }

            if ($totalCreated > 0 || $totalFound > 0 || $totalErrors > 0) {
                Log::info('CategoryRegistry: Seeding completed', [
                    'created' => $totalCreated,
                    'found' => $totalFound,
                    'errors' => $totalErrors,
                    'total' => count($this->categories),
                ]);
            } else {
                Log::debug('CategoryRegistry: No categories to seed or all already exist');
            }
        });
    }

    /**
     * Seed categories for a specific type, handling parent/child relationships.
     *
     * @param  string  $type  Category type
     * @param  array<int, array{module: string, type: string, name: string, slug: string, parent_slug: string|null, color: string|null, description: string|null}>  $categories  Categories to seed
     * @param  bool  $strict  If true, exceptions will be re-thrown to rollback transaction
     * @return array{created: int, found: int, errors: int} Statistics about the seeding operation
     */
    private function seedCategoriesForType(string $type, array $categories, bool $strict = false): array
    {
        $created = 0;
        $found = 0;
        $errors = 0;

        $existingCategories = Category::where('type', $type)
            ->get()
            ->keyBy(fn ($cat) => "{$cat->type}:{$cat->slug}");

        $parentMap = [];
        foreach ($categories as $category) {
            if (empty($category['parent_slug'])) {
                $key = "{$category['type']}:{$category['slug']}";

                if ($existingCategories->has($key)) {
                    $parentMap[$category['slug']] = $existingCategories->get($key);
                    $found++;

                    continue;
                }

                try {
                    $parentCategory = Category::create([
                        'name' => $category['name'],
                        'slug' => $category['slug'],
                        'type' => $category['type'],
                        'module' => $category['module'],
                        'color' => $category['color'],
                        'description' => $category['description'],
                    ]);

                    $parentMap[$category['slug']] = $parentCategory;
                    $existingCategories->put("{$parentCategory->type}:{$parentCategory->slug}", $parentCategory);
                    $created++;
                    Log::debug('CategoryRegistry: Created category', [
                        'module' => $category['module'],
                        'type' => $category['type'],
                        'slug' => $category['slug'],
                        'name' => $category['name'],
                    ]);
                } catch (\Exception $e) {
                    $errors++;
                    Log::error('CategoryRegistry: Failed to create category', [
                        'module' => $category['module'],
                        'type' => $category['type'],
                        'slug' => $category['slug'],
                        'error' => $e->getMessage(),
                    ]);

                    if ($strict) {
                        throw $e;
                    }
                }
            }
        }

        $maxDepth = 10;
        $depth = 0;

        while ($depth < $maxDepth) {
            $createdInThisPass = false;

            foreach ($categories as $category) {
                if (! empty($category['parent_slug'])) {
                    $key = "{$category['type']}:{$category['slug']}";

                    if ($existingCategories->has($key) || isset($parentMap[$category['slug']])) {
                        $found++;

                        continue;
                    }

                    $parent = $parentMap[$category['parent_slug']] ?? null;

                    if (! $parent) {
                        continue;
                    }

                    if ($parent->type !== $category['type']) {
                        Log::warning('CategoryRegistry: Parent category type mismatch', [
                            'module' => $category['module'],
                            'type' => $category['type'],
                            'slug' => $category['slug'],
                            'parent_slug' => $category['parent_slug'],
                            'parent_type' => $parent->type,
                        ]);

                        continue;
                    }

                    try {
                        $childCategory = Category::create([
                            'name' => $category['name'],
                            'slug' => $category['slug'],
                            'type' => $category['type'],
                            'module' => $category['module'],
                            'parent_id' => $parent->id,
                            'color' => $category['color'],
                            'description' => $category['description'],
                        ]);

                        $parentMap[$category['slug']] = $childCategory;
                        $existingCategories->put("{$childCategory->type}:{$childCategory->slug}", $childCategory);
                        $created++;
                        $createdInThisPass = true;
                        Log::debug('CategoryRegistry: Created child category', [
                            'module' => $category['module'],
                            'type' => $category['type'],
                            'slug' => $category['slug'],
                            'name' => $category['name'],
                            'parent_slug' => $category['parent_slug'],
                        ]);
                    } catch (\Exception $e) {
                        $errors++;
                        Log::error('CategoryRegistry: Failed to create child category', [
                            'module' => $category['module'],
                            'type' => $category['type'],
                            'slug' => $category['slug'],
                            'parent_slug' => $category['parent_slug'],
                            'error' => $e->getMessage(),
                        ]);

                        if ($strict) {
                            throw $e;
                        }
                    }
                }
            }

            if (! $createdInThisPass) {
                break;
            }

            $depth++;
        }

        if ($depth >= $maxDepth) {
            Log::warning('CategoryRegistry: Maximum depth reached while seeding categories', [
                'type' => $type,
            ]);
        }

        return [
            'created' => $created,
            'found' => $found,
            'errors' => $errors,
        ];
    }

    /**
     * Clean up categories for a module when uninstalling.
     *
     * Removes all categories that belong to the specified module.
     * Only deletes children that also belong to the same module.
     * Detects and warns about orphaned categories (children from other modules).
     *
     * Wrapped in a database transaction to ensure atomicity.
     *
     * @param  string  $moduleName  The module name (e.g., 'Blog', 'Newsletter')
     * @return array{deleted: int, orphaned: int} Statistics about the cleanup operation
     */
    public function cleanup(string $moduleName): array
    {
        $moduleName = strtolower($moduleName);

        return DB::transaction(function () use ($moduleName) {
            $categories = Category::where('module', $moduleName)->get();

            if ($categories->isEmpty()) {
                Log::info("CategoryRegistry: No categories found for module '{$moduleName}'");

                return [
                    'deleted' => 0,
                    'orphaned' => 0,
                ];
            }

            $categoryIds = $categories->pluck('id')->toArray();
            $categorySlugs = $categories->pluck('slug')->toArray();

            $orphanedCount = $this->detectOrphanedCategories($categoryIds, $moduleName);
            $totalToDelete = $this->countCategoryTree($categoryIds, $moduleName);
            $this->deleteCategoryTree($categoryIds, $moduleName);

            Log::info("CategoryRegistry: Cleaned up categories for module '{$moduleName}'", [
                'count' => count($categoryIds),
                'total_deleted' => $totalToDelete,
                'orphaned' => $orphanedCount,
                'slugs' => $categorySlugs,
            ]);

            return [
                'deleted' => $totalToDelete,
                'orphaned' => $orphanedCount,
            ];
        });
    }

    /**
     * Recursively delete a category tree, only deleting children from the same module.
     *
     * @param  array<string>  $categoryIds  Array of category IDs to delete
     * @param  string  $moduleName  Module name to filter children by
     */
    private function deleteCategoryTree(array $categoryIds, string $moduleName): void
    {
        if (empty($categoryIds)) {
            return;
        }

        $childCategories = Category::whereIn('parent_id', $categoryIds)
            ->where('module', $moduleName)
            ->pluck('id')
            ->toArray();

        if (! empty($childCategories)) {
            $this->deleteCategoryTree($childCategories, $moduleName);
        }

        Category::whereIn('id', $categoryIds)->delete();
    }

    /**
     * Detect and warn about orphaned categories (children from other modules).
     *
     * When a module's category is deleted, any children from other modules
     * will become orphaned (parent_id set to null by foreign key constraint).
     *
     * @param  array<string>  $categoryIds  Array of category IDs being deleted
     * @param  string  $moduleName  Module name being uninstalled
     * @return int Number of orphaned categories detected
     */
    private function detectOrphanedCategories(array $categoryIds, string $moduleName): int
    {
        if (empty($categoryIds)) {
            return 0;
        }

        $orphanedCategories = Category::whereIn('parent_id', $categoryIds)
            ->where('module', '!=', $moduleName)
            ->whereNotNull('module')
            ->get();

        if ($orphanedCategories->isNotEmpty()) {
            Log::warning('CategoryRegistry: Orphaned categories detected', [
                'uninstalling_module' => $moduleName,
                'orphaned_count' => $orphanedCategories->count(),
                'orphaned_categories' => $orphanedCategories->map(fn ($cat) => [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'type' => $cat->type,
                    'module' => $cat->module,
                    'parent_id' => $cat->parent_id,
                ])->toArray(),
            ]);
        }

        return $orphanedCategories->count();
    }

    /**
     * Count total categories in a tree (including children) that belong to the same module.
     *
     * @param  array<string>  $categoryIds  Array of category IDs to count
     * @param  string  $moduleName  Module name to filter children by
     * @return int Total count including all children
     */
    private function countCategoryTree(array $categoryIds, string $moduleName): int
    {
        if (empty($categoryIds)) {
            return 0;
        }

        $count = count($categoryIds);

        $childCategories = Category::whereIn('parent_id', $categoryIds)
            ->where('module', $moduleName)
            ->pluck('id')
            ->toArray();

        if (! empty($childCategories)) {
            $count += $this->countCategoryTree($childCategories, $moduleName);
        }

        return $count;
    }
}
