<?php

namespace App\Data;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Log;
use JsonSerializable;

/**
 * Dashboard Widget Data Transfer Object
 *
 * Represents a widget definition for the dashboard system.
 * Supports both static values and closures for dynamic computation.
 * Widgets can be displayed on the main overview dashboard, module-specific dashboards, or both.
 *
 * @example
 * ```php
 * // Static stat widget
 * new DashboardWidget(
 *     type: 'stat',
 *     title: 'Total Users',
 *     value: 150,
 *     icon: 'Users',
 *     module: 'Core'
 * );
 *
 * // Dynamic stat widget with closure
 * new DashboardWidget(
 *     type: 'stat',
 *     title: 'Total Users',
 *     value: fn() => User::count(),
 *     icon: 'Users',
 *     module: 'Core',
 *     scope: 'overview'
 * );
 *
 * // Chart widget with dynamic data
 * new DashboardWidget(
 *     type: 'chart',
 *     title: 'User Growth',
 *     value: 0,
 *     icon: 'TrendingUp',
 *     module: 'Core',
 *     chartType: 'line',
 *     data: fn() => $this->getUserGrowthData(),
 *     scope: 'detail'
 * );
 * ```
 */
readonly class DashboardWidget implements Arrayable, JsonSerializable
{
    /**
     * Create a new dashboard widget instance.
     *
     * @param  string  $type  Widget type. Valid types: 'stat' (statistics card), 'chart' (data visualization), 'activity' (recent activity list), 'system' (system status metrics), 'table' (data table)
     * @param  string  $title  Widget display title (e.g., 'Total Users', 'Recent Posts')
     * @param  string|int|Closure  $value  Static value (string/int) or closure returning string|int. For stat widgets, this is the main metric. For charts/activities, typically 0.
     * @param  string  $icon  Lucide React icon name (e.g., 'Users', 'FileText', 'TrendingUp'). Must be a valid icon from lucide-react package.
     * @param  string|null  $module  Module name that registered this widget (e.g., 'Core', 'Blog', 'Media'). Used for grouping and filtering.
     * @param  string|null  $group  Optional group name for organizing widgets within a module (e.g., 'User Management', 'Analytics', 'Activity'). Defaults to 'General'.
     * @param  string|Closure|null  $change  Change indicator (e.g., '+20.1%', '-5.2%') or closure returning string. Shows percentage change for stat widgets.
     * @param  'up'|'down'|null  $trend  Trend direction: 'up' (positive/green), 'down' (negative/red), or null (no trend indicator)
     * @param  int|null  $order  Display order for sorting widgets. Lower numbers appear first. If null, widgets maintain registration order.
     * @param  array|Closure|null  $data  Additional data for complex widgets. For charts: array of data points. For activities: array of activity items. For systems: array of metric objects. For tables: array of table row objects. Can be a closure returning array.
     * @param  string|null  $description  Optional description text displayed below the widget title (useful for charts and system widgets)
     * @param  string|null  $chartType  Chart type for chart widgets. Valid: 'bar' (bar chart), 'area' (area chart), 'line' (line chart). Required for chart widgets.
     * @param  array|null  $config  Chart configuration object defining data keys, colors, and labels. Used by recharts library for rendering. For table widgets, can contain 'columns' array for column definitions.
     * @param  string|null  $xAxisKey  X-axis data key for charts (e.g., 'month', 'date', 'name'). Defaults to 'name' if not provided.
     * @param  array|null  $dataKeys  Data keys for multi-value charts (e.g., ['revenue', 'expenses']). Used for area/line charts with multiple series.
     * @param  string|null  $scope  Widget visibility scope: 'overview' (main dashboard only), 'detail' (module dashboard only), 'both' (both dashboards, default)
     * @param  string|null  $version  Optional version string for cache invalidation. When widget definition changes, update version to invalidate old cache. Format: '1.0', 'v2', '2024-01-15', etc.
     */
    public function __construct(
        public string $type,
        public string $title,
        public string|int|Closure $value,
        public string $icon,
        public ?string $module = null,
        public ?string $group = null,
        public string|Closure|null $change = null,
        public ?string $trend = null,
        public ?int $order = null,
        public array|Closure|null $data = null,
        public ?string $description = null,
        public ?string $chartType = null,
        public ?array $config = null,
        public ?string $xAxisKey = null,
        public ?array $dataKeys = null,
        public ?string $scope = 'both',
        public ?string $version = null
    ) {}

    /**
     * Check if the value is a closure (dynamic computation).
     *
     * @return bool True if value is a closure, false if static value
     */
    public function hasValueCallback(): bool
    {
        return $this->value instanceof Closure;
    }

    /**
     * Check if the change indicator is a closure (dynamic computation).
     *
     * @return bool True if change is a closure, false if static value or null
     */
    public function hasChangeCallback(): bool
    {
        return $this->change instanceof Closure;
    }

    /**
     * Execute the value callback if it exists, otherwise return the static value.
     *
     * @return string|int Returns the computed value or 0 as fallback on error
     */
    public function getValue(): string|int
    {
        if ($this->value instanceof Closure) {
            try {
                return ($this->value)();
            } catch (\Throwable $e) {
                Log::error('DashboardWidget: Error computing value', [
                    'module' => $this->module,
                    'title' => $this->title,
                    'type' => $this->type,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return 0;
            }
        }

        return $this->value;
    }

    /**
     * Execute the change callback if it exists, otherwise return the static value.
     *
     * @return string|null Returns the computed change or null as fallback on error
     */
    public function getChange(): ?string
    {
        if ($this->change instanceof Closure) {
            try {
                return ($this->change)();
            } catch (\Throwable $e) {
                Log::error('DashboardWidget: Error computing change', [
                    'module' => $this->module,
                    'title' => $this->title,
                    'type' => $this->type,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return null;
            }
        }

        return $this->change;
    }

    /**
     * Check if the data is a closure (dynamic computation).
     *
     * @return bool True if data is a closure, false if static array or null
     */
    public function hasDataCallback(): bool
    {
        return $this->data instanceof Closure;
    }

    /**
     * Execute the data callback if it exists, otherwise return the static data.
     *
     * @return array|null Returns the computed data or null as fallback on error
     */
    public function getData(): ?array
    {
        if ($this->data instanceof Closure) {
            try {
                return ($this->data)();
            } catch (\Throwable $e) {
                Log::error('DashboardWidget: Error computing data', [
                    'module' => $this->module,
                    'title' => $this->title,
                    'type' => $this->type,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return null;
            }
        }

        return $this->data;
    }

    /**
     * Get the instance as an array.
     *
     * Executes any closures (value, change, data) to compute current values.
     * Returns a flat array representation suitable for JSON serialization
     * and frontend consumption.
     *
     * @return array{type: string, title: string, value: string|int, icon: string, module: string|null, group: string|null, change: string|null, trend: string|null, order: int|null, data: array|null, description: string|null, chartType: string|null, config: array|null, xAxisKey: string|null, dataKeys: array|null, scope: string|null}
     */
    public function toArray(): array
    {
        $array = [
            'type' => $this->type,
            'title' => $this->title,
            'value' => $this->getValue(),
            'icon' => $this->icon,
            'module' => $this->module,
            'change' => $this->getChange(),
            'trend' => $this->trend,
            'order' => $this->order,
        ];

        if ($this->group !== null) {
            $array['group'] = $this->group;
        }

        if ($this->data !== null) {
            $array['data'] = $this->getData();
        }

        if ($this->description !== null) {
            $array['description'] = $this->description;
        }

        if ($this->chartType !== null) {
            $array['chartType'] = $this->chartType;
        }

        if ($this->config !== null) {
            $array['config'] = $this->config;
        }

        if ($this->xAxisKey !== null) {
            $array['xAxisKey'] = $this->xAxisKey;
        }

        if ($this->dataKeys !== null) {
            $array['dataKeys'] = $this->dataKeys;
        }

        if ($this->scope !== null && $this->scope !== 'both') {
            $array['scope'] = $this->scope;
        }

        return $array;
    }

    /**
     * Specify the data which should be serialized to JSON.
     *
     * Implements JsonSerializable interface for automatic JSON encoding.
     * Delegates to toArray() to ensure closures are executed.
     *
     * @return array{type: string, title: string, value: string|int, icon: string, module: string|null, group: string|null, change: string|null, trend: string|null, order: int|null, data: array|null, description: string|null, chartType: string|null, config: array|null, xAxisKey: string|null, dataKeys: array|null, scope: string|null}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
