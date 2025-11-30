<?php

namespace Modules\Settings\Http\Requests;

use App\Services\SettingsService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * Accepts dynamic keys for bulk updates. Validates that each key exists
     * in the settings table and accepts any value type.
     *
     * Uses SettingsService to get keys from cache for better performance.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $settingsService = app(SettingsService::class);
        $settings = $settingsService->all();

        // Flatten grouped settings to get all keys
        $existingKeys = $settings->flatten(1)->pluck('key')->toArray();

        $rules = [];

        foreach ($existingKeys as $key) {
            $rules[$key] = ['nullable'];
        }

        return $rules;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
