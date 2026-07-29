<?php

namespace Modules\Bot\Enums;

enum BotMessageDirection: string
{
    case Outbound = 'outbound';
    case Inbound = 'inbound';
}
