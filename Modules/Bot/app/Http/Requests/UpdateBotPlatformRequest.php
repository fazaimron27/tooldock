<?php

/**
 * UpdateBotPlatformRequest
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

class UpdateBotPlatformRequest extends FormRequest
{
    public function authorize(): bool
    {
        $platform = $this->route('botPlatform');

        return $platform instanceof BotPlatform
            && $this->user()->can('update', $platform);
    }

    public function rules(): array
    {
        $platform = $this->route('botPlatform');
        $isDiscord = $platform instanceof BotPlatform
            ? $platform->driver === BotDriver::Discord
            : $this->input('driver') === BotDriver::Discord->value;

        return [
            'driver' => ['sometimes', 'string', 'in:'.implode(',', array_column(BotDriver::cases(), 'value'))],
            'name' => ['sometimes', 'string', 'max:255'],
            'credentials' => ['sometimes', 'array'],
            'credentials.webhook_url' => [
                Rule::requiredIf(fn (): bool => $this->has('credentials') && $isDiscord),
                'string',
                'regex:'.DiscordDriver::WEBHOOK_URL_PATTERN,
            ],
            'is_active' => ['boolean'],
        ];
    }
}
