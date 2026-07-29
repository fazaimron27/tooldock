<?php

/**
 * Routine Bot Registrar
 *
 * Registers Routine module bot commands with the Bot module's command registry.
 * Lives in Routine so that Bot remains fully optional — Routine is the one that
 * knows it wants to integrate with Bot, not the other way around.
 *
 * Loaded by RoutineServiceProvider only when Bot is installed and active.
 * Imports ONLY root-level interfaces — no compile-time dependency on Modules\Bot.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Routine\Services;

use App\Services\Registry\BotCommandRegistryInterface;
use App\Services\Registry\BotContextInterface;
use Modules\Routine\Models\Habit;

class RoutineBotRegistrar
{
    public function register(BotCommandRegistryInterface $registry): void
    {
        /**
         * /habits — list the user's active habits.
         *
         * Example reply:
         *   Your active habits:
         *   • Morning Exercise
         *   • Read 30 mins
         *   • Drink 2L water
         */
        $registry->register(
            key: 'habits',
            label: 'List your active habits',
            handler: function (BotContextInterface $context): void {
                $userId = $context->getOwnerUserId();

                if (! $userId) {
                    $context->reply('Your account could not be found.');

                    return;
                }

                $habits = Habit::where('user_id', $userId)
                    ->where('status', 'active')
                    ->orderBy('name')
                    ->pluck('name');

                if ($habits->isEmpty()) {
                    $context->reply('You have no active habits yet.');

                    return;
                }

                $list = $habits->map(fn ($name) => "• {$name}")->join("\n");
                $context->reply("Your active habits:\n{$list}");
            },
            schema: [],
        );
    }
}
