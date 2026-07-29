<?php

namespace Modules\Bot\Services;

use App\Services\Registry\BotContextInterface;
use Modules\Bot\BotUI\BotMessage;
use Modules\Bot\Drivers\DiscordDriver;
use Modules\Bot\Enums\BotDriver;
use Modules\Bot\Models\BotPlatform;

/**
 * BotContext
 *
 * Concrete implementation of BotContextInterface passed to every command handler.
 * Wraps BotManager::send() so handlers can reply without knowing the driver.
 */
class BotContext implements BotContextInterface
{
    public function __construct(
        private readonly BotPlatform $platform,
        private readonly string $chatUserId,
        private readonly array $arguments,
        private readonly BotManager $manager,
        private readonly string $ownerUserId,
        /** Discord only: interaction token for followup response after deferred ack. */
        private readonly ?string $interactionToken = null,
    ) {}

    public function reply(string $text): void
    {
        // Discord slash commands: use Interaction followup API.
        // The initial response was {"type":5} (deferred) sent synchronously by
        // DiscordInteractionController — now we send the actual content.
        if ($this->platform->driver === BotDriver::Discord && $this->interactionToken) {
            $appId = $this->platform->credentials['application_id'] ?? null;
            $driver = $this->manager->driver($this->platform->driver->value);

            if ($driver instanceof DiscordDriver) {
                $driver->sendFollowup($appId, $this->interactionToken, $text);
            }

            return;
        }

        // Telegram (and future drivers): reply to the sender's chat ID,
        // not the stored notification chat_id in credentials.
        $replyPlatform = clone $this->platform;
        $replyPlatform->credentials = array_merge(
            $this->platform->credentials ?? [],
            ['chat_id' => $this->chatUserId],
        );

        $this->manager
            ->to($replyPlatform)
            ->send(new BotMessage($text));
    }

    public function getPlatformId(): string
    {
        return $this->platform->id;
    }

    public function getUserId(): string
    {
        return $this->chatUserId;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getOwnerUserId(): string
    {
        return $this->ownerUserId;
    }
}
