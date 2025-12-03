<?php

namespace App\Services\Registry;

use App\Data\DashboardWidget;
use App\Services\Cache\CacheService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Registry for managing dashboard widget registration.
 *
 * Pure in-memory registry - no database persistence.
 * Widgets are registered during service provider boot and computed on-demand
 * via callbacks when getWidgets() is called.
 * Optional caching is available for expensive widget computations.
 *
 * **Cache Strategy:**
 * Uses a multi-tag strategy for selective cache invalidation:
 * - Global tag: `dashboard_widgets` - allows clearing all widgets
 * - Module tag: `dashboard_widgets:blog` - allows clearing only Blog widgets
 * - Example: Blog widget has tags `['dashboard_widgets', 'dashboard_widgets:blog']`
 *
 * **Cache Clearing:**
 * - `clearCache(null, 'Blog')` - Clears only Blog widgets (uses module tag)
 * - `clearCache()` - Clears all widgets (uses global tag)
 * - `clearCache($widget)` - Clears specific widget
 *
 * **Configuration:**
 * - Cache TTL: `config('dashboard.cache_ttl', 300)` seconds (default: 5 minutes)
 * - Cache Tags: `config('dashboard.use_cache_tags', true)` for selective invalidation
 * - Cache Tag Name: `config('dashboard.cache_tag', 'dashboard_widgets')`
 *
 * @see config/dashboard.php for configuration options
 * @see docs/cache-service-guide.md for cache tag strategy details
 */
class DashboardWidgetRegistry
{
    /**
     * @var array<int, DashboardWidget>
     */
    private array $widgets = [];

    public function __construct(
        private CacheService $cacheService
    ) {}

    /**
     * Valid widget types that are supported.
     */
    private const VALID_TYPES = ['stat', 'chart', 'activity', 'system'];

    /**
     * Valid widget scopes.
     */
    private const VALID_SCOPES = ['overview', 'detail', 'both'];

    /**
     * Cache key prefix for widget values.
     */
    private const CACHE_PREFIX = 'dashboard_widget:';

    /**
     * Get the cache TTL from configuration.
     *
     * @return int Cache TTL in seconds (0 to disable)
     */
    private function getCacheTtl(): int
    {
        return (int) Config::get('dashboard.cache_ttl', 300);
    }

    /**
     * Check if cache tags should be used.
     *
     * @return bool True if cache tags are enabled and supported
     */
    private function useCacheTags(): bool
    {
        return Config::get('dashboard.use_cache_tags', true);
    }

    /**
     * Get the cache tag name.
     *
     * @return string Cache tag name
     */
    private function getCacheTag(): string
    {
        return Config::get('dashboard.cache_tag', 'dashboard_widgets');
    }

    /**
     * Generate module-specific cache tag.
     *
     * Creates a tag like 'dashboard_widgets:blog' for module-specific cache clearing.
     * This allows selective invalidation of widgets for a specific module without
     * affecting widgets from other modules.
     *
     * @param  string  $module  Module name (e.g., 'Blog', 'Media')
     * @return string Module-specific cache tag
     */
    private function getModuleCacheTag(string $module): string
    {
        $baseTag = $this->getCacheTag();
        $moduleSlug = strtolower($module);

        return "{$baseTag}:{$moduleSlug}";
    }

    /**
     * Get cache tags for a widget (global + module-specific).
     *
     * Returns an array with both the global tag and module-specific tag.
     * This allows both global and selective cache clearing.
     *
     * @param  DashboardWidget  $widget  The widget
     * @return array<string> Array of cache tags
     */
    private function getWidgetCacheTags(DashboardWidget $widget): array
    {
        $tags = [$this->getCacheTag()];

        if ($widget->module !== null) {
            $tags[] = $this->getModuleCacheTag($widget->module);
        }

        return $tags;
    }

    /**
     * Register a widget definition.
     *
     * Widgets can have static values or closures for dynamic computation.
     * Closures are executed when getWidgets() is called, ensuring fresh data.
     * Widgets are validated before registration to ensure data integrity.
     *
     * @param  DashboardWidget  $widget  The widget to register
     *
     * @throws \InvalidArgumentException If widget validation fails (missing required fields, invalid type/scope)
     *
     * @example
     * ```php
     * $registry->register(
     *     new DashboardWidget(
     *         type: 'stat',
     *         title: 'Total Users',
     *         value: fn() => User::count(),
     *         icon: 'Users',
     *         module: 'Core',
     *         scope: 'overview'
     *     )
     * );
     * ```
     */
    public function register(DashboardWidget $widget): void
    {
        $this->validateWidget($widget);
        $this->widgets[] = $widget;
    }

    /**
     * Validate a widget before registration.
     *
     * @param  DashboardWidget  $widget  The widget to validate
     *
     * @throws \InvalidArgumentException If validation fails
     */
    private function validateWidget(DashboardWidget $widget): void
    {
        if (empty($widget->type)) {
            throw new \InvalidArgumentException('DashboardWidget: type is required');
        }

        if (empty($widget->title)) {
            throw new \InvalidArgumentException('DashboardWidget: title is required');
        }

        if (empty($widget->icon)) {
            throw new \InvalidArgumentException('DashboardWidget: icon is required');
        }

        if (! in_array($widget->type, self::VALID_TYPES, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'DashboardWidget: Invalid type "%s". Valid types are: %s',
                    $widget->type,
                    implode(', ', self::VALID_TYPES)
                )
            );
        }

        if ($widget->scope !== null && ! in_array($widget->scope, self::VALID_SCOPES, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'DashboardWidget: Invalid scope "%s". Valid scopes are: %s',
                    $widget->scope,
                    implode(', ', self::VALID_SCOPES)
                )
            );
        }

        if ($widget->type === 'chart') {
            if (empty($widget->chartType)) {
                Log::warning('DashboardWidget: Chart widget missing chartType', [
                    'module' => $widget->module,
                    'title' => $widget->title,
                ]);
            }

            if ($widget->data === null && ! $widget->hasDataCallback()) {
                Log::warning('DashboardWidget: Chart widget missing data', [
                    'module' => $widget->module,
                    'title' => $widget->title,
                ]);
            }
        }

        if ($widget->type === 'activity' && $widget->data === null && ! $widget->hasDataCallback()) {
            Log::warning('DashboardWidget: Activity widget missing data', [
                'module' => $widget->module,
                'title' => $widget->title,
            ]);
        }

        if ($widget->type === 'system' && $widget->data === null && ! $widget->hasDataCallback()) {
            Log::warning('DashboardWidget: System widget missing data', [
                'module' => $widget->module,
                'title' => $widget->title,
            ]);
        }
    }

    /**
     * Get all registered widgets with values computed.
     *
     * Executes any callbacks to get current values, then returns widgets
     * as arrays sorted by order (if provided).
     * Uses caching for expensive computations if enabled (see CACHE_TTL constant).
     *
     * @param  string|null  $scope  Optional scope filter: 'overview' (main dashboard), 'detail' (module dashboard), or null for all widgets
     * @return array<int, array{type: string, title: string, value: string|int, icon: string, module: string|null, group: string|null, change: string|null, trend: string|null, order: int|null, data: array|null, description: string|null, chartType: string|null, scope: string|null}>
     *
     * @example
     * ```php
     * // Get all widgets
     * $allWidgets = $registry->getWidgets();
     *
     * // Get only overview widgets (for main dashboard)
     * $overviewWidgets = $registry->getWidgets('overview');
     *
     * // Get only detail widgets (for module dashboards)
     * $detailWidgets = $registry->getWidgets('detail');
     * ```
     */
    public function getWidgets(?string $scope = null): array
    {
        $widgets = array_map(
            fn (DashboardWidget $widget) => $this->computeWidgetWithCache($widget),
            $this->widgets
        );

        if ($scope !== null) {
            $widgets = array_filter($widgets, function ($widget) use ($scope) {
                $widgetScope = $widget['scope'] ?? 'both';

                return $widgetScope === $scope || $widgetScope === 'both';
            });
        }

        usort($widgets, function ($a, $b) {
            $orderA = $a['order'] ?? PHP_INT_MAX;
            $orderB = $b['order'] ?? PHP_INT_MAX;

            if ($orderA === $orderB) {
                return 0;
            }

            return $orderA <=> $orderB;
        });

        return array_values($widgets);
    }

    /**
     * Get widgets for a specific module.
     *
     * Filters widgets by module name (case-insensitive) and optionally by scope.
     * Useful for displaying module-specific dashboards.
     *
     * @param  string  $module  Module name (case-insensitive, e.g., 'Core', 'Blog', 'Media')
     * @param  string|null  $scope  Optional scope filter: 'overview', 'detail', or null for all widgets in the module
     * @return array<int, array{type: string, title: string, value: string|int, icon: string, module: string|null, group: string|null, change: string|null, trend: string|null, order: int|null, data: array|null, description: string|null, chartType: string|null, scope: string|null}>
     *
     * @example
     * ```php
     * // Get all widgets for Core module
     * $coreWidgets = $registry->getWidgetsForModule('Core');
     *
     * // Get only detail widgets for Blog module
     * $blogDetailWidgets = $registry->getWidgetsForModule('Blog', 'detail');
     * ```
     */
    public function getWidgetsForModule(string $module, ?string $scope = null): array
    {
        $widgets = $this->getWidgets($scope);

        return array_values(array_filter($widgets, function ($widget) use ($module) {
            return strtolower($widget['module'] ?? '') === strtolower($module);
        }));
    }

    /**
     * Get overview widgets for the main dashboard.
     *
     * Returns widgets with scope 'overview' or 'both', grouped by module.
     * For each module, prioritizes stat widgets and limits to top 3 per module
     * to keep the overview dashboard concise and performant.
     * Other widget types (charts, activities, systems) are included without limit.
     *
     * @return array<int, array{type: string, title: string, value: string|int, icon: string, module: string|null, group: string|null, change: string|null, trend: string|null, order: int|null, data: array|null, description: string|null, chartType: string|null, scope: string|null}>
     *
     * @example
     * ```php
     * // In DashboardController
     * $overviewWidgets = $widgetRegistry->getOverviewWidgets();
     * return Inertia::render('Dashboard', ['widgets' => $overviewWidgets]);
     * ```
     */
    public function getOverviewWidgets(): array
    {
        $widgets = $this->getWidgets('overview');

        $grouped = [];
        foreach ($widgets as $widget) {
            $module = $widget['module'] ?? 'Other';
            if (! isset($grouped[$module])) {
                $grouped[$module] = [];
            }
            $grouped[$module][] = $widget;
        }

        $overview = [];
        foreach ($grouped as $module => $moduleWidgets) {
            $statWidgets = array_filter($moduleWidgets, fn ($w) => $w['type'] === 'stat');
            $otherWidgets = array_filter($moduleWidgets, fn ($w) => $w['type'] !== 'stat');

            $statWidgets = array_slice($statWidgets, 0, 3);
            $overview = array_merge($overview, $statWidgets, $otherWidgets);
        }

        usort($overview, function ($a, $b) {
            $orderA = $a['order'] ?? PHP_INT_MAX;
            $orderB = $b['order'] ?? PHP_INT_MAX;

            if ($orderA === $orderB) {
                return 0;
            }

            return $orderA <=> $orderB;
        });

        return array_values($overview);
    }

    /**
     * Compute widget values with optional caching.
     *
     * Caches widget computations if the widget has closures and caching is enabled.
     * Cache key is based on widget module, title, and type to ensure uniqueness.
     *
     * @param  DashboardWidget  $widget  The widget to compute
     * @return array<int|string, mixed> The widget as an array with computed values
     */
    private function computeWidgetWithCache(DashboardWidget $widget): array
    {
        $cacheTtl = $this->getCacheTtl();

        if ($cacheTtl <= 0) {
            return $widget->toArray();
        }

        $hasClosures = $widget->hasValueCallback() || $widget->hasChangeCallback() || $widget->hasDataCallback();

        if (! $hasClosures) {
            return $widget->toArray();
        }

        $cacheKey = $this->generateCacheKey($widget);
        $tags = $this->useCacheTags() ? $this->getWidgetCacheTags($widget) : null;

        return $this->cacheService->remember(
            $cacheKey,
            $cacheTtl,
            fn () => $widget->toArray(),
            $tags
        );
    }

    /**
     * Generate a unique cache key for a widget.
     *
     * Cache key includes:
     * - Environment prefix (prevents collisions across dev/staging/prod)
     * - Module name
     * - Widget type
     * - Title hash (for uniqueness)
     * - Optional version (for cache invalidation when widget definition changes)
     *
     * @param  DashboardWidget  $widget  The widget
     * @return string The cache key
     */
    private function generateCacheKey(DashboardWidget $widget): string
    {
        $parts = [];

        $envPrefix = $this->getEnvironmentPrefix();
        if ($envPrefix !== '') {
            $parts[] = $envPrefix;
        }

        $parts[] = self::CACHE_PREFIX;
        $parts[] = $widget->module ?? 'unknown';
        $parts[] = $widget->type;
        $parts[] = md5($widget->title);

        if ($widget->version !== null) {
            $parts[] = $widget->version;
        }

        return implode(':', $parts);
    }

    /**
     * Get environment prefix for cache keys.
     *
     * Prefixes cache keys with environment name to prevent collisions
     * when multiple environments share the same Redis instance.
     * Returns empty string if environment prefixing is disabled.
     *
     * @return string Environment prefix (e.g., 'local', 'staging', 'production') or empty string
     */
    private function getEnvironmentPrefix(): string
    {
        if (! Config::get('dashboard.use_environment_prefix', true)) {
            return '';
        }

        return app()->environment();
    }

    /**
     * Clear cached widget values.
     *
     * Useful for cache invalidation when underlying data changes.
     * If a specific widget is provided, only that widget's cache is cleared.
     * If null is provided, all widget caches are cleared using cache tags (if enabled).
     *
     * @param  DashboardWidget|null  $widget  Optional specific widget to clear, or null to clear all widget caches
     * @param  string|null  $module  Optional module name to clear all widgets for a specific module
     * @return void
     *
     * @example
     * ```php
     * // Clear cache for a specific widget after data update
     * $userCountWidget = new DashboardWidget(...);
     * $registry->clearCache($userCountWidget);
     *
     * // Clear all widget caches (e.g., after bulk data import)
     * $registry->clearCache();
     *
     * // Clear all widgets for a specific module
     * $registry->clearCache(null, 'Core');
     * ```
     */
    public function clearCache(?DashboardWidget $widget = null, ?string $module = null): void
    {
        if ($widget !== null) {
            $cacheKey = $this->generateCacheKey($widget);
            $tags = $this->useCacheTags() ? $this->getWidgetCacheTags($widget) : null;
            $this->cacheService->forget($cacheKey, $tags);
        } elseif ($module !== null) {
            if ($this->useCacheTags()) {
                $moduleTag = $this->getModuleCacheTag($module);
                $this->cacheService->flush($moduleTag, 'DashboardWidgetRegistry');

                Log::debug('DashboardWidgetRegistry: Cleared cache for module widgets', [
                    'module' => $module,
                    'tag' => $moduleTag,
                ]);
            } else {
                $clearedCount = 0;
                foreach ($this->widgets as $widget) {
                    if (strcasecmp($widget->module ?? '', $module) === 0) {
                        $cacheKey = $this->generateCacheKey($widget);
                        $this->cacheService->forget($cacheKey);
                        $clearedCount++;
                    }
                }

                if ($clearedCount > 0) {
                    Log::debug('DashboardWidgetRegistry: Cleared cache for module widgets', [
                        'module' => $module,
                        'count' => $clearedCount,
                    ]);
                }
            }
        } else {
            $tags = $this->useCacheTags() ? $this->getCacheTag() : null;
            $this->cacheService->flush($tags, 'DashboardWidgetRegistry');
        }
    }
}
