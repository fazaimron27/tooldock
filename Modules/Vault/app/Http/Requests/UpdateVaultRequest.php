<?php

/**
 * Update Vault Request
 *
 * Form request for validating and authorizing vault item updates.
 * Handles input sanitization and ownership-based authorization.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Vault\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Vault\Models\Vault;

/**
 * Class UpdateVaultRequest
 *
 * Validates vault update data and ensures the authenticated user
 * owns the vault being modified.
 *
 * @see \Modules\Vault\Http\Controllers\VaultController::update()
 */
class UpdateVaultRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     *
     * Converts empty strings to null for optional fields to ensure
     * consistent database storage.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->email === '' ? null : $this->email,
            'url' => $this->url === '' ? null : $this->url,
            'username' => $this->username === '' ? null : $this->username,
            'issuer' => $this->issuer === '' ? null : $this->issuer,
            'value' => $this->value === '' ? null : $this->value,
            'totp_secret' => $this->totp_secret === '' ? null : $this->totp_secret,
            'totp_algorithm' => $this->totp_algorithm === '' ? null : $this->totp_algorithm,
            'totp_digits' => $this->totp_digits === '' ? null : $this->totp_digits,
            'totp_period' => $this->totp_period === '' ? null : $this->totp_period,
            'category_id' => $this->category_id === '' ? null : $this->category_id,
            'fields' => (is_array($this->fields) && empty(array_filter($this->fields))) ? null : $this->fields,
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
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(Vault::TYPES)],
            'username' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'issuer' => ['nullable', 'string', 'max:255'],
            'value' => ['nullable', 'string'],
            'totp_secret' => ['nullable', 'string'],
            'totp_algorithm' => ['nullable', 'string', 'in:sha1,sha256,sha512'],
            'totp_digits' => ['nullable', 'integer', 'in:6,8'],
            'totp_period' => ['nullable', 'integer', 'in:30,60'],
            'fields' => ['nullable', 'array'],
            'url' => ['nullable', 'url', 'max:2048'],
            'category_id' => [
                'nullable',
                'string',
                Rule::exists('categories', 'id'),
            ],
            'is_favorite' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True if the user can update the vault
     */
    public function authorize(): bool
    {
        $vault = $this->route('vault');

        return $this->user()->can('update', $vault);
    }
}
