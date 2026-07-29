<?php

/**
 * StoreBotPlatformRequest
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Bot\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Bot\Drivers\DiscordDriver;
use Modules\Bot\Enums\BotDriver;
use Modules\Bot\Models\BotPlatform;

class StoreBotPlatformRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', BotPlatform::class);
    }

    public function rules(): array
    {
        return [
            'driver' => ['required', 'string', 'in:'.implode(',', array_column(BotDriver::cases(), 'value'))],
            'name' => ['required', 'string', 'max:255'],
            'credentials' => ['required', 'array'],
            'credentials.webhook_url' => [
                Rule::requiredIf(fn (): bool => $this->input('driver') === BotDriver::Discord->value),
                'string',
                'regex:'.DiscordDriver::WEBHOOK_URL_PATTERN,
            ],
            'is_active' => ['boolean'],
        ];
    }
}
