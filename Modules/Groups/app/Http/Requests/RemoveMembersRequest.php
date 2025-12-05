<?php

namespace Modules\Groups\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class RemoveMembersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Requires the user to have permission to update the group.
     */
    public function authorize(): bool
    {
        $group = $this->route('group');

        return Gate::allows('update', $group);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Validates that at least one user ID is provided and that all user IDs
     * exist in the users table.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'user_ids.*' => [
                'required',
                'integer',
                'exists:'.(config('permission.table_names.users') ?? 'users').',id',
            ],
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
            'user_ids.required' => 'At least one user must be selected.',
            'user_ids.array' => 'User IDs must be an array.',
            'user_ids.min' => 'At least one user must be selected.',
            'user_ids.*.required' => 'Each user ID is required.',
            'user_ids.*.integer' => 'Each user ID must be an integer.',
            'user_ids.*.exists' => 'One or more selected users do not exist.',
        ];
    }
}
