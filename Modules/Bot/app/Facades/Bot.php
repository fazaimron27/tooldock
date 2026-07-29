<?php

namespace Modules\Bot\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Bot\Services\BotManager;

/**
 * @method static \Modules\Bot\Services\BotManager to(\Modules\Bot\Models\BotPlatform $platform)
 * @method static bool send(\Modules\Bot\BotUI\BotMessage $message, ?string $commandKey = null)
 * @method static array test(\Modules\Bot\Models\BotPlatform $platform)
 *
 * @see BotManager
 */
class Bot extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'bot';
    }
}
