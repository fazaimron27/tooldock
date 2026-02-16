<?php

namespace Modules\Treasury\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Modules\Treasury\Models\Budget;

class StoreBudgetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Budget::class);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_recurring' => $this->boolean('is_recurring'),
            'rollover_enabled' => $this->boolean('rollover_enabled'),
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
            'category_id' => [
                'required',
                'uuid',
                Rule::exists('categories', 'id')->where('type', 'transaction_category'),
            ],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],  // ISO 4217, defaults to user's reference currency
            'is_recurring' => ['sometimes', 'boolean'],
            'rollover_enabled' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Enforce one budget per category per user
            // Multiple budgets per category causes "shared spending" confusion
            $exists = \Modules\Treasury\Models\Budget::where('user_id', Auth::id())
                ->where('category_id', $this->category_id)
                ->exists();

            if ($exists) {
                $validator->errors()->add('category_id', 'This category already has a budget. Use sub-categories for different spending purposes.');
            }
        });
    }
}
