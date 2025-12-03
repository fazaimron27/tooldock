<?php

namespace Modules\Newsletter\Services;

use App\Services\Registry\SettingsRegistry;
use Modules\Settings\Enums\SettingType;

/**
 * Handles settings registration for the Newsletter module.
 */
class NewsletterSettingsRegistrar
{
    /**
     * Register newsletter module settings.
     *
     * Group name should be lowercase module name for consistency.
     */
    public function register(SettingsRegistry $registry, string $moduleName): void
    {
        $registry->register(
            module: $moduleName,
            group: 'newsletter',
            key: 'campaigns_per_page',
            value: '10',
            type: SettingType::Integer,
            label: 'Campaigns Per Page',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'newsletter',
            key: 'max_posts_per_campaign',
            value: '20',
            type: SettingType::Integer,
            label: 'Maximum Posts Per Campaign',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'newsletter',
            key: 'newsletter_default_sort',
            value: 'created_at',
            type: SettingType::Text,
            label: 'Default Sort Column',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'newsletter',
            key: 'newsletter_default_sort_direction',
            value: 'desc',
            type: SettingType::Text,
            label: 'Default Sort Direction',
            isSystem: false
        );
    }
}
