<?php

namespace Modules\Sandbox\Services;

use App\Services\Registry\SignalHandlerRegistry;
use Modules\Sandbox\Services\Handlers\SandboxInboundReceivedHandler;

class SandboxSignalRegistrar
{
    private const MODULE_NAME = 'Sandbox';

    /**
     * @var array<class-string>
     */
    private const HANDLERS = [
        SandboxInboundReceivedHandler::class,
    ];

    public function register(SignalHandlerRegistry $registry): void
    {
        foreach (self::HANDLERS as $handlerClass) {
            $registry->register(self::MODULE_NAME, $handlerClass);
        }
    }
}
