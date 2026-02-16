<?php

/**
 * Update Budget Request
 *
 * Validates requests to update an existing budget template or period override.
 * Enforces category uniqueness per user when changing categories and supports
 * both template-level and period-level update types.
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
 * Class UpdateBudgetRequest
 *
 * Handles validation for budget updates with template/period distinction.
 */
class UpdateBudgetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('budget'));
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_recurring' => $this->has('is_recurring') ? $this->boolean('is_recurring') : $this->is_recurring,
            'rollover_enabled' => $this->has('rollover_enabled') ? $this->boolean('rollover_enabled') : $this->rollover_enabled,
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
            'category_id' => [
                'sometimes',
                'required',
                'uuid',
                Rule::exists('categories', 'id')->where('type', 'transaction_category'),
            ],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'is_recurring' => ['sometimes', 'boolean'],
            'rollover_enabled' => ['sometimes', 'boolean'],
            'update_type' => ['sometimes', 'string', 'in:template,period'],
            'period' => ['sometimes', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
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
            $budget = $this->route('budget');

            if ($this->has('category_id') && $this->category_id !== $budget->category_id) {
                $exists = Budget::where('user_id', Auth::id())
                    ->where('category_id', $this->category_id)
                    ->where('id', '!=', $budget->id)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('category_id', 'This category already has a budget. Use sub-categories for different spending purposes.');
                }
            }
        });
    }
}
