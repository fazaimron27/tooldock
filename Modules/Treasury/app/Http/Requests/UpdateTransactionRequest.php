<?php

/**
 * Update Transaction Request
 *
 * Validates requests to update an existing financial transaction. Supports
 * partial updates with 'sometimes' rules, enforces wallet ownership,
 * prevents expenses from savings wallets, and handles attachment management.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Modules\Treasury\Models\Wallet;

/**
 * Class UpdateTransactionRequest
 *
 * Handles validation for transaction updates with partial field support.
 */
class UpdateTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('transaction'));
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'category_id' => $this->category_id === '' ? null : $this->category_id,
            'description' => $this->description === '' ? null : $this->description,
            'name' => $this->name === '' ? null : $this->name,
            'destination_wallet_id' => $this->destination_wallet_id === '' ? null : $this->destination_wallet_id,
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
            'wallet_id' => [
                'sometimes',
                'required',
                'uuid',
                Rule::exists('wallets', 'id')->where(function ($query) {
                    return $query->where('user_id', Auth::id());
                }),
                function ($attribute, $value, $fail) {
                    if ($this->input('type') === 'expense') {
                        $wallet = Wallet::find($value);
                        if ($wallet?->type === 'savings') {
                            $fail('Cannot create expenses from savings wallets. Transfer funds out first.');
                        }
                    }
                },
            ],
            'destination_wallet_id' => [
                'nullable',
                'uuid',
                'different:wallet_id',
                'required_if:type,transfer',
                Rule::exists('wallets', 'id')->where(function ($query) {
                    return $query->where('user_id', Auth::id());
                }),
            ],
            'category_id' => [
                'nullable',
                'uuid',
                Rule::exists('categories', 'id')->where('type', 'transaction_category'),
            ],
            'type' => ['sometimes', 'required', 'string', 'in:income,expense,transfer'],
            'name' => ['nullable', 'string', 'max:100'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'fee' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:255'],
            'date' => ['sometimes', 'required', 'date'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.0000000001'],
            'original_currency' => ['nullable', 'string', 'max:3'],
            'attachment_ids' => ['nullable', 'array'],
            'attachment_ids.*' => ['uuid', 'exists:media_files,id'],
            'remove_attachment_ids' => ['nullable', 'array'],
            'remove_attachment_ids.*' => ['uuid'],
        ];
    }
}
