<?php

/**
 * Store Budget Request
 *
 * Validates requests to create a new budget template. Ensures category
 * uniqueness per user (one budget per category), validates the category
 * exists as a transaction category, and normalizes boolean fields.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Modules\Treasury\Models\Budget;

/**
 * Class StoreBudgetRequest
 *
 * Handles validation for budget creation with category uniqueness enforcement.
 */
class StoreBudgetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Budget::class);
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
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
            'currency' => ['nullable', 'string', 'size:3'],
            'is_recurring' => ['sometimes', 'boolean'],
            'rollover_enabled' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator  The validator instance
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $exists = \Modules\Treasury\Models\Budget::where('user_id', Auth::id())
                ->where('category_id', $this->category_id)
                ->exists();

            if ($exists) {
                $validator->errors()->add('category_id', 'This category already has a budget. Use sub-categories for different spending purposes.');
            }
        });
    }
}
