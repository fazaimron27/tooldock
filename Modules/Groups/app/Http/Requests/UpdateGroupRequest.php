<?php

namespace Modules\Groups\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Modules\Core\App\Models\User;
use Modules\Groups\Models\Group;

class UpdateGroupRequest extends FormRequest
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
        $groupId = $this->route('group')->id ?? null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Group::class)->ignore($groupId),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique(Group::class)->ignore($groupId),
            ],
            'description' => ['nullable', 'string'],
            'members' => ['nullable', 'array'],
            'members.*' => ['exists:'.(config('permission.table_names.users') ?? 'users').',id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ];
    }
}
