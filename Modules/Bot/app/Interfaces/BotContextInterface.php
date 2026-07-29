<?php

/**
 * BotContextInterface (Bot-module alias)
 *
 * Re-exports the root-level BotContextInterface for use within the Bot module.
 * External modules should import from App\Services\Registry\BotContextInterface.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Bot\Interfaces;

use Modules\Bot\Models\BotPlatform;

/**
 * @see \App\Services\Registry\BotContextInterface
 */
interface BotContextInterface extends \App\Services\Registry\BotContextInterface
{
    /**
     * The full BotPlatform model for this interaction.
     * Available to Bot-internal handlers that need platform credentials.
     */
    public function getPlatform(): BotPlatform;
}
