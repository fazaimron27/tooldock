<?php

/**
 * Update Wallet Request
 *
 * Validates requests to update an existing wallet. Supports partial updates
 * with 'sometimes' rules for name, type, and currency fields. Validates
 * wallet type against category slugs and enforces ISO 4217 currency codes.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class UpdateWalletRequest
 *
 * Handles validation for wallet updates with partial field support.
 */
class UpdateWalletRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('wallet'));
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'description' => $this->description === '' ? null : $this->description,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'type' => [
                'sometimes',
                'required',
                'string',
                Rule::exists('categories', 'slug')->where('type', 'wallet_type'),
            ],
            'currency' => ['sometimes', 'required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
