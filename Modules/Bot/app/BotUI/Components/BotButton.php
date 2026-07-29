<?php

namespace Modules\Bot\BotUI\Components;

use Modules\Bot\BotUI\BotComponent;

/**
 * A clickable action button.
 * Renders as InlineKeyboardButton on Telegram, ActionRow Button on Discord.
 *
 * Pass $url to create an external link button (Telegram: opens URL in browser).
 * Leave $url null for a standard callback/action button.
 */
class BotButton extends BotComponent
{
    public function __construct(
        public readonly string $label,
        public readonly string $action,
        public readonly string $style = 'primary',
        public readonly ?string $url = null,
    ) {}

    public function toArray(): array
    {
        return [
            'type' => 'button',
            'label' => $this->label,
            'action' => $this->action,
            'style' => $this->style,
            'url' => $this->url,
        ];
    }
}
