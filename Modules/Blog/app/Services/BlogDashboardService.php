<?php

namespace Modules\Blog\Services;

use App\Data\DashboardWidget;
use App\Services\Registry\DashboardWidgetRegistry;
use Modules\Blog\Models\Post;

/**
 * Handles dashboard widget registration and data retrieval for the Blog module.
 */
class BlogDashboardService
{
    /**
     * Register all dashboard widgets for the Blog module.
     */
    public function registerWidgets(DashboardWidgetRegistry $widgetRegistry, string $moduleName): void
    {
        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Total Posts',
                value: fn () => Post::count(),
                icon: 'FileText',
                module: $moduleName,
                order: 20,
                scope: 'overview'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'chart',
                title: 'Posts Published',
                value: 0,
                icon: 'BarChart3',
                module: $moduleName,
                description: 'Posts published over the last 6 months',
                chartType: 'bar',
                data: fn () => $this->getPostsPublishedData(),
                order: 21,
                scope: 'detail'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'activity',
                title: 'Recent Posts',
                value: 0,
                icon: 'FileText',
                module: $moduleName,
                description: 'Latest published posts',
                data: fn () => $this->getRecentPostsActivity(),
                order: 22,
                scope: 'detail'
            )
        );
    }

    /**
     * Get posts published data for chart widget.
     *
     * Uses a single query with GROUP BY instead of multiple separate queries.
     * Always returns an array with 6 months of data, even if no posts exist.
     */
    private function getPostsPublishedData(): array
    {
        try {
            $now = now();
            $startDate = $now->copy()->subMonths(5)->startOfMonth();
            $endDate = $now->copy()->endOfMonth();

            $results = Post::selectRaw('
                    DATE_TRUNC(\'month\', published_at)::date as month,
                    COUNT(*) as count
                ')
                ->whereNotNull('published_at')
                ->whereBetween('published_at', [$startDate, $endDate])
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->keyBy(function ($item) {
                    $monthValue = is_string($item->month) ? $item->month : (string) $item->month;

                    return substr($monthValue, 0, 7);
                })
                ->map(fn ($item) => (int) $item->count)
                ->toArray();

            $months = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = $now->copy()->subMonths($i);
                $monthKey = $month->format('Y-m');

                $months[] = [
                    'name' => $month->format('M Y'),
                    'value' => $results[$monthKey] ?? 0,
                ];
            }

            return $months;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('BlogDashboardService: Error getting posts published data', [
                'error' => $e->getMessage(),
            ]);

            $now = now();
            $months = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = $now->copy()->subMonths($i);
                $months[] = [
                    'name' => $month->format('M Y'),
                    'value' => 0,
                ];
            }

            return $months;
        }
    }

    /**
     * Get recent posts activity for activity widget.
     */
    private function getRecentPostsActivity(): array
    {
        return Post::whereNotNull('published_at')
            ->latest('published_at')
            ->limit(5)
            ->get()
            ->map(function ($post) {
                return [
                    'id' => $post->id,
                    'title' => "Post published: {$post->title}",
                    'timestamp' => $post->published_at->diffForHumans(),
                    'icon' => 'FileText',
                    'iconColor' => 'bg-green-500',
                ];
            })
            ->toArray();
    }
}
