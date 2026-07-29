<?php

/**
 * BotBotRegistrar
 *
 * Registers the Bot module's own bot commands (commands built into the bot itself,
 * not contributed by other modules). Other modules contribute via their own registrars.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Bot\Services;

use App\Services\Registry\BotCommandRegistryInterface;
use App\Services\Registry\BotContextInterface;
use Modules\Bot\Models\BotConnection;
use Modules\Core\Models\User;

class BotBotRegistrar
{
    public function register(BotCommandRegistryInterface $registry): void
    {
        /**
         * /start — link your Tool Dock account.
         *
         * The actual logic lives in InboundWebhookHandler::handleStart()
         * and is intercepted before this handler would ever be called.
         * Registered here so bot:discord-sync includes it in the Discord command list.
         */
        $registry->register(
            key: 'start',
            label: 'Link your Tool Dock account to this bot',
            handler: function (BotContextInterface $context): void {
                // Intercepted by InboundWebhookHandler — this handler is never reached.
            },
            schema: [],
        );

        /**
         * /whoami — show the currently connected Tool Dock account.
         *
         * Example reply:
         *   You are connected as:
         *   Name:     Faza Imron
         *   Email:    faza@example.com
         *   Platform: @fazaimron27
         */
        $registry->register(
            key: 'whoami',
            label: 'Show your connected Tool Dock account',
            handler: function (BotContextInterface $context): void {
                $ownerUserId = $context->getOwnerUserId();
                $platformUserId = $context->getUserId();
                $platformId = $context->getPlatformId();

                $user = User::find($ownerUserId);

                if (! $user) {
                    $context->reply('Could not find your account. Try /start to re-link.');

                    return;
                }

                $connection = BotConnection::where('bot_platform_id', $platformId)
                    ->where('platform_user_id', $platformUserId)
                    ->first();

                $platformUsername = $connection?->platform_username
                    ? "@{$connection->platform_username}"
                    : "#{$platformUserId}";

                $context->reply(
                    "You are connected as:\n"
                        ."*{$user->name}*\n"
                        ."`{$user->email}`\n"
                        ."Platform: {$platformUsername}"
                );
            },
            schema: [],
        );
    }
}
