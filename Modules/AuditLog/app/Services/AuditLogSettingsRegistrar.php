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
        $registry->registerMany($moduleName, 'auditlog', [
            'retention' => [
                'label' => 'Data Retention',
                'description' => 'Configure how long audit data is kept',
                'settings' => [
                    ['key' => 'retention_days', 'value' => '90', 'type' => SettingType::Integer, 'label' => 'Audit Log Retention (Days)'],
                ],
            ],
            'cleanup' => [
                'label' => 'Cleanup Schedule',
                'description' => 'Configure automatic cleanup of old audit logs',
                'settings' => [
                    ['key' => 'scheduled_cleanup_enabled', 'value' => '1', 'type' => SettingType::Boolean, 'label' => 'Enable Scheduled Cleanup'],
                    ['key' => 'cleanup_schedule_time', 'value' => '02:00', 'type' => SettingType::Text, 'label' => 'Cleanup Schedule Time (HH:MM)'],
                ],
            ],
            'export' => [
                'label' => 'Export Settings',
                'description' => 'Configure export behavior for audit logs',
                'settings' => [
                    ['key' => 'export_chunk_size', 'value' => '500', 'type' => SettingType::Integer, 'label' => 'Export Chunk Size'],
                ],
            ],
        ]);
    }
}
