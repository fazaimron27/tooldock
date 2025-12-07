<?php

namespace Modules\AuditLog\Services;

use App\Services\Registry\SettingsRegistry;
use Modules\Settings\Enums\SettingType;

/**
 * Handles settings registration for the AuditLog module.
 */
class AuditLogSettingsRegistrar
{
    /**
     * Register default settings for the AuditLog module.
     */
    public function register(SettingsRegistry $registry, string $moduleName): void
    {
        $registry->register(
            module: $moduleName,
            group: 'auditlog',
            key: 'retention_days',
            value: '90',
            type: SettingType::Integer,
            label: 'Audit Log Retention (Days)',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'auditlog',
            key: 'scheduled_cleanup_enabled',
            value: '1',
            type: SettingType::Boolean,
            label: 'Enable Scheduled Cleanup',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'auditlog',
            key: 'export_chunk_size',
            value: '500',
            type: SettingType::Integer,
            label: 'Export Chunk Size',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'auditlog',
            key: 'cleanup_schedule_time',
            value: '02:00',
            type: SettingType::Text,
            label: 'Cleanup Schedule Time (HH:MM)',
            isSystem: false
        );
    }
}
