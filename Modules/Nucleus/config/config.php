<?php

/**
 * Nucleus Module Configuration
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

return [
    'name' => 'Nucleus',

    /*
    |--------------------------------------------------------------------------
    | Editor Defaults
    |--------------------------------------------------------------------------
    |
    | These values are used as fallbacks when no user or system settings
    | are configured for the Nucleus Monaco editor.
    |
    */
    'editor_theme' => env('NUCLEUS_EDITOR_THEME', 'vs-dark'),
    'word_wrap' => env('NUCLEUS_WORD_WRAP', 'on'),
    'font_size' => (int) env('NUCLEUS_FONT_SIZE', 14),
];
