<?php

namespace Modules\Treasury\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AllocateGoalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('allocate', $this->route('goal'));
    }

    /**
     * Prepare the data for validation.
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
        $goal = $this->route('goal');

        return [
            'wallet_id' => [
                'required',
                'uuid',
                Rule::exists('wallets', 'id')->where(function ($query) {
                    return $query->where('user_id', Auth::id())
                        ->where('is_active', true);
                }),
                // Source wallet must be different from goal's linked savings wallet
                function ($attribute, $value, $fail) use ($goal) {
                    if ($goal && $value === $goal->wallet_id) {
                        $fail('The source wallet must be different from the goal\'s linked savings wallet.');
                    }
                },
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get the custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'wallet_id.exists' => 'Please select an active wallet that you own.',
        ];
    }
}
