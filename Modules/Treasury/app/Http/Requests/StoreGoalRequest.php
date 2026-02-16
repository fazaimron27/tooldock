<?php

/**
 * Store Goal Request
 *
 * Validates requests to create a new savings goal. Ensures the linked
 * savings wallet is active, user-owned, and not already assigned to
 * another active goal. Normalizes nullable fields.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Modules\Treasury\Models\TreasuryGoal;

/**
 * Class StoreGoalRequest
 *
 * Handles validation for goal creation with wallet uniqueness enforcement.
 */
class StoreGoalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', TreasuryGoal::class);
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
            'deadline' => $this->deadline === '' ? null : $this->deadline,
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
                    return $query->where('user_id', Auth::id())
                        ->where('type', 'savings')
                        ->where('is_active', true);
                }),
                Rule::unique('treasury_goals', 'wallet_id')
                    ->where(fn ($query) => $query->where('is_completed', false)),
            ],
            'category_id' => [
                'nullable',
                'uuid',
                Rule::exists('categories', 'id')->where('type', 'goal'),
            ],
            'name' => ['required', 'string', 'max:150'],
            'target_amount' => ['required', 'numeric', 'min:1'],
            'deadline' => ['nullable', 'date', 'after:today'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'wallet_id.required' => 'A savings wallet is required to create a goal.',
            'wallet_id.exists' => 'Please select an active savings wallet that you own.',
            'wallet_id.unique' => 'This wallet already has an active goal. Complete or delete the existing goal first, or choose a different wallet.',
        ];
    }
}
