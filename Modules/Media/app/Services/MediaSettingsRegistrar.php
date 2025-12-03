<?php

namespace Modules\Media\Services;

use App\Services\Registry\SettingsRegistry;
use Modules\Settings\Enums\SettingType;

/**
 * Handles settings registration for the Media module.
 */
class MediaSettingsRegistrar
{
    /**
     * Register media module settings.
     */
    public function register(SettingsRegistry $registry, string $moduleName): void
    {
        $registry->register(
            module: $moduleName,
            group: 'media',
            key: 'max_file_size',
            value: '10240',
            type: SettingType::Integer,
            label: 'Max File Size (KB)',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'media',
            key: 'default_storage_disk',
            value: 'public',
            type: SettingType::Text,
            label: 'Default Storage Disk',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'media',
            key: 'temporary_file_retention_hours',
            value: '24',
            type: SettingType::Integer,
            label: 'Temporary File Retention (Hours)',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'media',
            key: 'image_max_dimension',
            value: '2000',
            type: SettingType::Integer,
            label: 'Image Max Dimension (px)',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'media',
            key: 'image_quality',
            value: '85',
            type: SettingType::Integer,
            label: 'Image Quality (1-100)',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'media',
            key: 'allowed_mime_types',
            value: 'image/jpeg,image/png,image/gif,image/webp,image/svg+xml,application/pdf',
            type: SettingType::Text,
            label: 'Allowed MIME Types (comma-separated)',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'media',
            key: 'prefer_webp',
            value: '0',
            type: SettingType::Integer,
            label: 'Prefer WebP Format (0=No, 1=Yes)',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'media',
            key: 'upload_rate_limit_per_minute',
            value: '10',
            type: SettingType::Integer,
            label: 'Upload Rate Limit (per minute for authenticated users)',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'media',
            key: 'upload_rate_limit_guest_per_minute',
            value: '5',
            type: SettingType::Integer,
            label: 'Upload Rate Limit (per minute for guests)',
            isSystem: false
        );
    }
}
