<?php

/**
 * Bot Message Controller
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Bot\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Bot\Models\BotMessage;

class BotMessageController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('bot.bridge.view');

        $messages = BotMessage::with('platform')
            ->forUser($request->user())
            ->orderBy('created_at', 'desc')
            ->paginate(30)
            ->through(fn (BotMessage $m) => [
                'id' => $m->id,
                'platform' => $m->platform?->name,
                'driver' => $m->platform?->driver?->value,
                'direction' => $m->direction->value,
                'command_key' => $m->command_key,
                'status' => $m->status->value,
                'error_message' => $m->error_message,
                'created_at' => $m->created_at->toDateTimeString(),
            ]);

        return Inertia::render('Modules::Bot/Messages', [
            'messages' => $messages,
        ]);
    }
}
