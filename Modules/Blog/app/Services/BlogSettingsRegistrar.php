<?php

namespace Modules\Blog\Services;

use App\Services\Registry\SettingsRegistry;
use Modules\Settings\Enums\SettingType;

/**
 * Handles settings registration for the Blog module.
 */
class BlogSettingsRegistrar
{
    /**
     * Register blog module settings.
     *
     * Group name should be lowercase module name for consistency.
     */
    public function register(SettingsRegistry $registry, string $moduleName): void
    {
        $registry->register(
            module: $moduleName,
            group: 'blog',
            key: 'posts_per_page',
            value: '10',
            type: SettingType::Integer,
            label: 'Posts Per Page',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'blog',
            key: 'blog_default_sort',
            value: 'created_at',
            type: SettingType::Text,
            label: 'Default Sort Column',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'blog',
            key: 'blog_default_sort_direction',
            value: 'desc',
            type: SettingType::Text,
            label: 'Default Sort Direction',
            isSystem: false
        );
    }
}
