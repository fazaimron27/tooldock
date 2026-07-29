<?php

/**
 * Update Settings Request.
 *
 * Validates bulk settings updates with dynamic key validation
 * against existing settings in the database.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Settings\Http\Requests;

use App\Services\Core\SettingsService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Settings\Models\Setting;

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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $settingsService = app(SettingsService::class);
        $settings = $settingsService->all();

        $existingKeys = $settings->flatten(1)->pluck('key')->toArray();

        $rules = [];

        foreach ($existingKeys as $key) {
            $rules[$key] = ['nullable'];
        }

        return $rules;
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('updateAny', Setting::class);
    }
}
