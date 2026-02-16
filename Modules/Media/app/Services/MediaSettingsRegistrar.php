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
        $registry->registerMany($moduleName, 'media', [
            'storage' => [
                'label' => 'Storage Settings',
                'description' => 'Configure file storage and limits',
                'settings' => [
                    ['key' => 'max_file_size', 'value' => '10240', 'type' => SettingType::Integer, 'label' => 'Max File Size (KB)'],
                    ['key' => 'default_storage_disk', 'value' => 'public', 'type' => SettingType::Text, 'label' => 'Default Storage Disk'],
                    ['key' => 'temporary_file_retention_hours', 'value' => '24', 'type' => SettingType::Integer, 'label' => 'Temporary File Retention (Hours)'],
                    ['key' => 'allowed_mime_types', 'value' => 'image/jpeg,image/png,image/gif,image/webp,image/svg+xml,application/pdf', 'type' => SettingType::Text, 'label' => 'Allowed MIME Types (comma-separated)'],
                ],
            ],
            'images' => [
                'label' => 'Image Processing',
                'description' => 'Configure image processing and optimization',
                'settings' => [
                    ['key' => 'image_max_dimension', 'value' => '2000', 'type' => SettingType::Integer, 'label' => 'Image Max Dimension (px)'],
                    ['key' => 'image_quality', 'value' => '85', 'type' => SettingType::Integer, 'label' => 'Image Quality (1-100)'],
                    ['key' => 'prefer_webp', 'value' => '0', 'type' => SettingType::Integer, 'label' => 'Prefer WebP Format (0=No, 1=Yes)'],
                ],
            ],
            'rate_limits' => [
                'label' => 'Rate Limits',
                'description' => 'Configure upload rate limiting',
                'settings' => [
                    ['key' => 'upload_rate_limit_per_minute', 'value' => '10', 'type' => SettingType::Integer, 'label' => 'Upload Rate Limit (per minute for authenticated users)'],
                    ['key' => 'upload_rate_limit_guest_per_minute', 'value' => '5', 'type' => SettingType::Integer, 'label' => 'Upload Rate Limit (per minute for guests)'],
                ],
            ],
        ]);
    }
}
