<?php

/**
 * Bot Module — API Routes
 *
 * Inbound webhooks are handled generically by the Hook module's HookInboundController.
 * The DiscordInboundProcessor is registered in BotServiceProvider to intercept
 * Discord-specific requests (Ed25519 verification + PING/PONG) transparently at
 * the standard Hook inbound URL: POST /api/v1/hook/inbound/{slug}
 *
 * No dedicated Bot API routes are required at this time.
 */
