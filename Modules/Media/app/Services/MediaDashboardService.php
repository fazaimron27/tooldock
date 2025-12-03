<?php

namespace Modules\Media\Services;

use App\Data\DashboardWidget;
use App\Services\Registry\DashboardWidgetRegistry;
use Modules\Media\Models\MediaFile;

/**
 * Handles dashboard widget registration and data retrieval for the Media module.
 */
class MediaDashboardService
{
    /**
     * Register all dashboard widgets for the Media module.
     */
    public function registerWidgets(DashboardWidgetRegistry $widgetRegistry, string $moduleName): void
    {
        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Total Media Files',
                value: fn () => MediaFile::permanent()->count(),
                icon: 'Image',
                module: $moduleName,
                order: 40,
                scope: 'overview'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'system',
                title: 'Storage Usage',
                value: 0,
                icon: 'HardDrive',
                module: $moduleName,
                description: 'Media storage metrics',
                data: fn () => $this->getStorageUsageMetrics(),
                order: 41,
                scope: 'detail'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'activity',
                title: 'Recent Uploads',
                value: 0,
                icon: 'Upload',
                module: $moduleName,
                description: 'Latest media file uploads',
                data: fn () => $this->getRecentMediaActivity(),
                order: 42,
                scope: 'detail'
            )
        );
    }

    /**
     * Get storage usage metrics for system widget.
     *
     * Uses a single query with conditional aggregation instead of multiple queries.
     */
    private function getStorageUsageMetrics(): array
    {
        $metrics = MediaFile::selectRaw('
                COUNT(*) FILTER (WHERE is_temporary = false) as permanent_count,
                COUNT(*) FILTER (WHERE is_temporary = true) as temporary_count,
                COALESCE(SUM(size) FILTER (WHERE is_temporary = false), 0) as total_size
            ')
            ->first();

        $totalFiles = (int) ($metrics->permanent_count ?? 0);
        $totalSize = (int) ($metrics->total_size ?? 0);
        $temporaryFiles = (int) ($metrics->temporary_count ?? 0);

        $totalSizeMB = round($totalSize / 1024 / 1024, 2);

        $maxStorage = 10 * 1024;
        $usagePercentage = $maxStorage > 0 ? round(($totalSizeMB / $maxStorage) * 100, 1) : 0;

        return [
            [
                'label' => 'Total Storage Used',
                'value' => "{$totalSizeMB} MB",
                'percentage' => min($usagePercentage, 100),
                'color' => $usagePercentage > 80 ? 'destructive' : ($usagePercentage > 60 ? 'warning' : 'success'),
            ],
            [
                'label' => 'Permanent Files',
                'value' => (string) $totalFiles,
                'percentage' => $totalFiles > 0 ? 100 : 0,
                'color' => 'primary',
            ],
            [
                'label' => 'Temporary Files',
                'value' => (string) $temporaryFiles,
                'percentage' => $temporaryFiles > 0 ? 50 : 0,
                'color' => 'warning',
            ],
        ];
    }

    /**
     * Get recent media uploads activity for activity widget.
     */
    private function getRecentMediaActivity(): array
    {
        return MediaFile::permanent()
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function ($file) {
                $fileSize = round($file->size / 1024, 2);

                return [
                    'id' => $file->id,
                    'title' => "Uploaded: {$file->filename} ({$fileSize} KB)",
                    'timestamp' => $file->created_at->diffForHumans(),
                    'icon' => 'Upload',
                    'iconColor' => 'bg-purple-500',
                ];
            })
            ->toArray();
    }
}
