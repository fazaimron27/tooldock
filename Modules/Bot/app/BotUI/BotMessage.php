<?php

namespace Modules\Bot\BotUI;

/**
 * Value object representing a message to be sent through a bot platform.
 * Holds plain text AND an optional array of BotComponents (buttons, cards, etc.).
 */
class BotMessage
{
    /**
     * @param  string  $text  Main message body (Markdown-friendly)
     * @param  BotComponent[]  $components  Optional UI components
     */
    public function __construct(
        public readonly string $text,
        public readonly array $components = [],
    ) {}

    /**
     * Fluent constructor.
     */
    public static function make(string $text, array $components = []): self
    {
        return new self($text, $components);
    }

    public function hasComponents(): bool
    {
        return ! empty($this->components);
    }
}
