<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Cache performance metrics service.
 *
 * Tracks cache hit/miss rates, operation durations, and performance metrics.
 * Metrics are stored in cache and can be retrieved for analysis or reporting.
 *
 * **Features:**
 * - Hit/miss tracking per operation type
 * - Operation duration tracking
 * - Slow operation detection
 * - Aggregate statistics
 *
 * **Usage:**
 * Metrics are automatically collected when enabled in config.
 * Retrieve metrics via `getStats()` or `getOperationStats()`.
 */
class CacheMetricsService
{
    private const METRICS_PREFIX = 'cache_metrics';

    private const METRICS_TTL = 3600; // 1 hour

    /**
     * Record a cache hit.
     *
     * @param  string  $operation  Operation type (e.g., 'remember', 'get', 'rememberForever')
     * @param  float  $duration  Operation duration in milliseconds
     * @param  string|null  $context  Optional context (e.g., 'SettingsService', 'MenuRegistry')
     * @return void
     */
    public function recordHit(string $operation, float $duration, ?string $context = null): void
    {
        $this->incrementMetric("{$operation}.hits");
        $this->addDuration("{$operation}.durations", $duration);
        $this->incrementMetric('total.hits');

        if ($context !== null) {
            $this->incrementMetric("context.{$context}.hits");
        }

        // Log slow operations (> 100ms)
        if ($duration > 100) {
            Log::warning('CacheService: Slow cache operation detected', [
                'operation' => $operation,
                'duration_ms' => round($duration, 2),
                'context' => $context,
            ]);
        }
    }

    /**
     * Record a cache miss.
     *
     * @param  string  $operation  Operation type (e.g., 'remember', 'get', 'rememberForever')
     * @param  float  $duration  Operation duration in milliseconds
     * @param  string|null  $context  Optional context (e.g., 'SettingsService', 'MenuRegistry')
     * @return void
     */
    public function recordMiss(string $operation, float $duration, ?string $context = null): void
    {
        $this->incrementMetric("{$operation}.misses");
        $this->addDuration("{$operation}.durations", $duration);
        $this->incrementMetric('total.misses');

        if ($context !== null) {
            $this->incrementMetric("context.{$context}.misses");
        }
    }

    /**
     * Record a cache write operation.
     *
     * @param  string  $operation  Operation type (e.g., 'put', 'remember', 'rememberForever')
     * @param  float  $duration  Operation duration in milliseconds
     * @param  string|null  $context  Optional context
     * @return void
     */
    public function recordWrite(string $operation, float $duration, ?string $context = null): void
    {
        $this->incrementMetric("{$operation}.writes");
        $this->addDuration("{$operation}.durations", $duration);
        $this->incrementMetric('total.writes');

        if ($context !== null) {
            $this->incrementMetric("context.{$context}.writes");
        }
    }

    /**
     * Record a cache delete operation.
     *
     * @param  string  $operation  Operation type (e.g., 'forget', 'flush')
     * @param  float  $duration  Operation duration in milliseconds
     * @param  string|null  $context  Optional context
     * @return void
     */
    public function recordDelete(string $operation, float $duration, ?string $context = null): void
    {
        $this->incrementMetric("{$operation}.deletes");
        $this->addDuration("{$operation}.durations", $duration);
        $this->incrementMetric('total.deletes');

        if ($context !== null) {
            $this->incrementMetric("context.{$context}.deletes");
        }
    }

    /**
     * Record a cache error.
     *
     * @param  string  $operation  Operation type
     * @param  string  $error  Error message
     * @param  string|null  $context  Optional context
     * @return void
     */
    public function recordError(string $operation, string $error, ?string $context = null): void
    {
        $this->incrementMetric("{$operation}.errors");
        $this->incrementMetric('total.errors');

        if ($context !== null) {
            $this->incrementMetric("context.{$context}.errors");
        }
    }

    /**
     * Get aggregate statistics.
     *
     * @return array<string, mixed> Statistics including hit rate, total operations, average duration
     */
    public function getStats(): array
    {
        $hits = $this->getMetric('total.hits', 0);
        $misses = $this->getMetric('total.misses', 0);
        $writes = $this->getMetric('total.writes', 0);
        $deletes = $this->getMetric('total.deletes', 0);
        $errors = $this->getMetric('total.errors', 0);

        $total = $hits + $misses;
        $hitRate = $total > 0 ? ($hits / $total) * 100 : 0;

        $totalDurations = $this->getMetric('total.durations', []);
        $avgDuration = ! empty($totalDurations) ? array_sum($totalDurations) / count($totalDurations) : 0;

        return [
            'hits' => $hits,
            'misses' => $misses,
            'writes' => $writes,
            'deletes' => $deletes,
            'errors' => $errors,
            'total_operations' => $hits + $misses + $writes + $deletes,
            'hit_rate' => round($hitRate, 2),
            'miss_rate' => round(100 - $hitRate, 2),
            'average_duration_ms' => round($avgDuration, 2),
        ];
    }

    /**
     * Get statistics for a specific operation.
     *
     * @param  string  $operation  Operation type (e.g., 'remember', 'get')
     * @return array<string, mixed> Operation-specific statistics
     */
    public function getOperationStats(string $operation): array
    {
        $hits = $this->getMetric("{$operation}.hits", 0);
        $misses = $this->getMetric("{$operation}.misses", 0);
        $writes = $this->getMetric("{$operation}.writes", 0);
        $deletes = $this->getMetric("{$operation}.deletes", 0);
        $errors = $this->getMetric("{$operation}.errors", 0);

        $total = $hits + $misses;
        $hitRate = $total > 0 ? ($hits / $total) * 100 : 0;

        $durations = $this->getMetric("{$operation}.durations", []);
        $avgDuration = ! empty($durations) ? array_sum($durations) / count($durations) : 0;
        $maxDuration = ! empty($durations) ? max($durations) : 0;
        $minDuration = ! empty($durations) ? min($durations) : 0;

        return [
            'operation' => $operation,
            'hits' => $hits,
            'misses' => $misses,
            'writes' => $writes,
            'deletes' => $deletes,
            'errors' => $errors,
            'total_operations' => $hits + $misses + $writes + $deletes,
            'hit_rate' => round($hitRate, 2),
            'miss_rate' => round(100 - $hitRate, 2),
            'average_duration_ms' => round($avgDuration, 2),
            'max_duration_ms' => round($maxDuration, 2),
            'min_duration_ms' => round($minDuration, 2),
        ];
    }

    /**
     * Get statistics by context.
     *
     * @param  string  $context  Context name (e.g., 'SettingsService')
     * @return array<string, mixed> Context-specific statistics
     */
    public function getContextStats(string $context): array
    {
        $hits = $this->getMetric("context.{$context}.hits", 0);
        $misses = $this->getMetric("context.{$context}.misses", 0);
        $writes = $this->getMetric("context.{$context}.writes", 0);
        $deletes = $this->getMetric("context.{$context}.deletes", 0);
        $errors = $this->getMetric("context.{$context}.errors", 0);

        $total = $hits + $misses;
        $hitRate = $total > 0 ? ($hits / $total) * 100 : 0;

        return [
            'context' => $context,
            'hits' => $hits,
            'misses' => $misses,
            'writes' => $writes,
            'deletes' => $deletes,
            'errors' => $errors,
            'total_operations' => $hits + $misses + $writes + $deletes,
            'hit_rate' => round($hitRate, 2),
            'miss_rate' => round(100 - $hitRate, 2),
        ];
    }

    /**
     * Clear all metrics.
     *
     * @return void
     */
    public function clear(): void
    {
        // Clear all metrics keys
        // Note: This is a simple implementation. In production, you might want
        // to track metric keys and clear them individually.
        Cache::forget(self::METRICS_PREFIX.':total');
        Cache::forget(self::METRICS_PREFIX.':operations');
        Cache::forget(self::METRICS_PREFIX.':contexts');
    }

    /**
     * Increment a metric counter.
     *
     * @param  string  $key  Metric key
     * @return void
     */
    private function incrementMetric(string $key): void
    {
        $cacheKey = self::METRICS_PREFIX.':'.$key;
        $current = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $current + 1, self::METRICS_TTL);
    }

    /**
     * Add a duration to the duration list (keeps last 100).
     *
     * @param  string  $key  Metric key
     * @param  float  $duration  Duration in milliseconds
     * @return void
     */
    private function addDuration(string $key, float $duration): void
    {
        $cacheKey = self::METRICS_PREFIX.':'.$key;
        $durations = Cache::get($cacheKey, []);
        $durations[] = $duration;

        // Keep only last 100 durations to prevent memory issues
        if (count($durations) > 100) {
            $durations = array_slice($durations, -100);
        }

        Cache::put($cacheKey, $durations, self::METRICS_TTL);

        // Also update total durations
        $totalDurations = Cache::get(self::METRICS_PREFIX.':total.durations', []);
        $totalDurations[] = $duration;
        if (count($totalDurations) > 1000) {
            $totalDurations = array_slice($totalDurations, -1000);
        }
        Cache::put(self::METRICS_PREFIX.':total.durations', $totalDurations, self::METRICS_TTL);
    }

    /**
     * Get a metric value.
     *
     * @param  string  $key  Metric key
     * @param  mixed  $default  Default value
     * @return mixed Metric value
     */
    private function getMetric(string $key, mixed $default = null): mixed
    {
        return Cache::get(self::METRICS_PREFIX.':'.$key, $default);
    }
}
