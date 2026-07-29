<?php

/**
 * StoreOutboundRequest
 *
 * Form request for validating and authorizing outbound webhook creation.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Hook\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Hook\Enums\HookOutboundProvider;
use Modules\Hook\Http\Controllers\HookOutboundController;
use Modules\Hook\Models\HookOutbound;
use Modules\Hook\Rules\PublicHttpUrl;
use Modules\Hook\Services\HookEventRegistry;

/**
 * Class StoreOutboundRequest
 *
 * @see HookOutboundController::store()
 */
class StoreOutboundRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(HookEventRegistry $eventRegistry): array
    {
        $provider = HookOutboundProvider::tryFrom((string) $this->input('provider', HookOutboundProvider::Generic->value))
            ?? HookOutboundProvider::Generic;
        $providerConfigRules = ['nullable', 'array'];

        if ($provider !== HookOutboundProvider::Generic) {
            $providerConfigRules[] = 'array:'.implode(',', $provider->configKeys());
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'provider' => ['nullable', 'string', Rule::enum(HookOutboundProvider::class)],
            'provider_config' => $providerConfigRules,
            'provider_config.*' => ['nullable', 'string', 'max:2048'],
            'target_url' => [
                Rule::when(
                    fn () => $provider === HookOutboundProvider::Generic,
                    ['required', 'url', 'max:2048', new PublicHttpUrl],
                    ['nullable', 'url', 'max:2048'],
                ),
            ],
            'method' => ['nullable', 'string', 'in:GET,POST,PUT,PATCH,DELETE'],
            'trigger' => ['nullable', 'string', 'max:100', Rule::in(array_keys($eventRegistry->all()))],
            'headers' => ['nullable', 'array'],
            'payload_template' => ['nullable', 'array'],
            'description' => ['nullable', 'string', 'max:1000'],
            ...$provider->providerConfigValidationRules(),
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', HookOutbound::class);
    }
}
