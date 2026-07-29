<?php

namespace Modules\Bot\BotUI;

/**
 * Abstract base for all BotUI components.
 * Drivers call toArray() to get their platform-specific representation.
 */
abstract class BotComponent
{
    abstract public function toArray(): array;
}
