<?php

namespace Modules\Bot\BotUI\Components;

use Modules\Bot\BotUI\BotComponent;

/**
 * A structured card / embed with title and key-value fields.
 * Renders as formatted Markdown message on Telegram, Rich Embed on Discord.
 */
class BotCard extends BotComponent
{
    /**
     * @param  array<string, string>  $fields
     */
    public function __construct(
        public readonly string $title,
        public readonly array $fields = [],
        public readonly string $color = '#5865F2',
    ) {}

    public function toArray(): array
    {
        return ['type' => 'card', 'title' => $this->title, 'fields' => $this->fields, 'color' => $this->color];
    }
}
