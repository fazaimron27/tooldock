<?php

namespace Modules\Groups\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TransferMembersRequest extends FormRequest
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
     * Validates that:
     * - At least one user ID is provided
     * - All user IDs exist and are members of the current group
     * - Target group exists and is different from the current group
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $currentGroupId = $this->route('group')->id;

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
            'user_ids.required' => 'At least one user must be selected.',
            'user_ids.array' => 'User IDs must be an array.',
            'user_ids.min' => 'At least one user must be selected.',
            'user_ids.*.required' => 'Each user ID is required.',
            'user_ids.*.exists' => 'One or more selected users do not exist or are not members of this group.',
            'target_group_id.required' => 'Target group is required.',
            'target_group_id.exists' => 'The selected target group does not exist.',
            'target_group_id.not_in' => 'The target group must be different from the current group.',
        ];
    }
}
