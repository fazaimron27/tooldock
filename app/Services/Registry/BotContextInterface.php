<?php

/**
 * Bot Context Interface
 *
 * Passed to every registered bot command handler.
 * Allows the handler to reply to the user without knowing the underlying
 * platform or any Bot-module internals.
 *
 * Lives at root so any module can depend on it safely,
 * even when the Bot module itself is optional/uninstalled.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace App\Services\Registry;

/**
 * Interface BotContextInterface
 */
interface BotContextInterface
{
    /**
     * Send a plain-text reply back to the user through the same platform.
     * Markdown formatting (e.g. *bold*, `code`) is supported.
     */
    public function reply(string $text): void;

    /**
     * The ID of the platform model this interaction arrived from.
     */
    public function getPlatformId(): string;

    /**
     * The external chat user ID (Telegram user_id, Discord member id, etc.).
     */
    public function getUserId(): string;

    /**
     * Arguments passed alongside the command (e.g. /balance usd → ['usd']).
     *
     * @return list<string>
     */
    public function getArguments(): array;

    /**
     * The application user ID who owns this bot integration.
     * Use this to scope queries to the correct user's data — do NOT
     * query bot_platforms directly from command handlers.
     */
    public function getOwnerUserId(): string;
}
