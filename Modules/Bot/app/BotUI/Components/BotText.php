<?php

namespace Modules\Bot\BotUI\Components;

use Modules\Bot\BotUI\BotComponent;

/**
 * A plain or formatted text block.
 * Styles: normal | bold | italic | code
 */
class BotText extends BotComponent
{
    public function __construct(
        public readonly string $content,
        public readonly string $style = 'normal',
    ) {}

    public function toArray(): array
    {
        return ['type' => 'text', 'content' => $this->content, 'style' => $this->style];
    }
}
