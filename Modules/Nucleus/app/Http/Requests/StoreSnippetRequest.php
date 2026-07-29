<?php

/**
 * StoreSnippetRequest
 *
 * Validates the request data when saving a JSON snippet to the Nucleus library.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Nucleus\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Nucleus\Models\NucleusSnippet;

/**
 * Class StoreSnippetRequest
 *
 * Ensures the title and raw_json fields meet requirements before persisting.
 */
class StoreSnippetRequest extends FormRequest
{
    private const MAX_RAW_JSON_BYTES = 64_000;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', NucleusSnippet::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'raw_json' => [
                'required',
                'string',
                'json',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (is_string($value) && strlen($value) > self::MAX_RAW_JSON_BYTES) {
                        $fail('The JSON payload must not exceed 64 KB.');
                    }
                },
            ],
        ];
    }
}
