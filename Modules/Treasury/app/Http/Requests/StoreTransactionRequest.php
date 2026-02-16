<?php

namespace Modules\Treasury\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Models\Wallet;

class StoreTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Transaction::class);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'category_id' => $this->category_id === '' ? null : $this->category_id,
            'description' => $this->description === '' ? null : $this->description,
            'name' => $this->name === '' ? null : $this->name,
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
                'required',
                'uuid',
                Rule::exists('wallets', 'id')->where(function ($query) {
                    return $query->where('user_id', Auth::id());
                }),
                // Block expenses from savings-type wallets
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
            'type' => ['required', 'string', 'in:income,expense,transfer'],
            'name' => ['nullable', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'fee' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.0000000001'],  // For cross-currency transfers
            'original_currency' => ['nullable', 'string', 'max:3'],  // Source wallet currency for audit
            'attachment_ids' => ['nullable', 'array'],
            'attachment_ids.*' => ['uuid', 'exists:media_files,id'],
        ];
    }
}
