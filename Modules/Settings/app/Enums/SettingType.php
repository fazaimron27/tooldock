<?php

/**
 * Setting Type Enum.
 *
 * Defines the available data types for application settings.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Settings\Enums;

enum SettingType: string
{
    case Text = 'text';
    case Boolean = 'boolean';
    case Integer = 'integer';
    case Textarea = 'textarea';
    case Select = 'select';
    case Currency = 'currency';
    case Percentage = 'percentage';
}
