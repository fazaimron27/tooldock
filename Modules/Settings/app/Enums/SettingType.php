<?php

namespace Modules\Settings\Enums;

enum SettingType: string
{
    case Text = 'text';
    case Boolean = 'boolean';
    case Integer = 'integer';
    case Textarea = 'textarea';
}
