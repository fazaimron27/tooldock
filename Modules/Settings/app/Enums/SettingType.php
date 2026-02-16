<?php

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
