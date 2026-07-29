<?php

/**
 * BotConnectController
 *
 * Handles the web-side of the bot account linking flow.
 *
 * Flow:
 *  1. User types /start in bot → gets a signed, 10-minute URL
 *  2. User clicks URL → arrives here (must be authenticated)
 *  3. show()  → verify signed URL → render confirmation page
 *  4. store() → create BotConnection → redirect with flash
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Bot\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Bot\Models\BotConnection;
use Modules\Bot\Models\BotPlatform;

class BotConnectController extends Controller
{
    /**
     * Show the account connection confirmation page.
     *
     * Validates the signed URL and renders the confirmation UI.
     * If already connected, redirects with an informational message.
     */
    public function show(Request $request): Response|RedirectResponse
    {
        abort_unless($request->hasValidRelativeSignature(), 403, 'This link has expired or is invalid.');

        $platformId = $request->query('bot_platform_id');
        $platformUserId = $request->query('platform_user_id');
        $platformUsername = $request->query('platform_username');

        $platform = BotPlatform::where('id', $platformId)->where('is_active', true)->firstOrFail();

        // Already connected — no need to link again
        $existing = BotConnection::where('bot_platform_id', $platformId)
            ->where('platform_user_id', $platformUserId)
            ->first();

        if ($existing) {
            return redirect()->route('bot.index')
                ->with('success', 'Your account is already connected.');
        }

        return Inertia::render('Modules::Bot/Connect', [
            'platform' => $platform->only('id', 'name', 'driver'),
            'platformUsername' => $platformUsername,
            'connectUrl' => $request->getRequestUri(),
        ]);
    }

    /**
     * Persist the account connection.
     */
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->hasValidRelativeSignature(), 403, 'This link has expired or is invalid.');

        $validated = Validator::make($request->query(), [
            'bot_platform_id' => [
                'required',
                'uuid',
                Rule::exists('bot_platforms', 'id')->where('is_active', true),
            ],
            'platform_user_id' => ['required', 'string', 'max:255'],
            'platform_username' => ['required', 'string', 'max:255'],
        ])->validate();

        BotConnection::firstOrCreate(
            [
                'bot_platform_id' => $validated['bot_platform_id'],
                'platform_user_id' => $validated['platform_user_id'],
            ],
            [
                'platform_username' => $validated['platform_username'],
                'user_id' => $request->user()->id,
            ]
        );

        return redirect()->route('bot.index')
            ->with('success', 'Account connected! You can now use bot commands.');
    }
}
