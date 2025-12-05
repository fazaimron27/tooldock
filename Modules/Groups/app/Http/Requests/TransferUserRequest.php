<?php

namespace Modules\Groups\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TransferUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $group = $this->route('group');

        return Gate::allows('update', $group);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $currentGroupId = $this->route('group')->id;

        return [
            'user_id' => [
                'required',
                'integer',
                'exists:'.(config('permission.table_names.users') ?? 'users').',id',
                Rule::exists('group_user', 'user_id')->where('group_id', $currentGroupId),
            ],
            'target_group_id' => [
                'required',
                'integer',
                'exists:groups,id',
                Rule::notIn([$currentGroupId]),
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
            'user_id.required' => 'User ID is required.',
            'user_id.exists' => 'The selected user does not exist.',
            'target_group_id.required' => 'Target group is required.',
            'target_group_id.exists' => 'The selected target group does not exist.',
            'target_group_id.not_in' => 'The target group must be different from the current group.',
        ];
    }
}
