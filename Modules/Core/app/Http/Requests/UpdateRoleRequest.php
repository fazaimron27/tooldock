<?php

/**
 * Update Role Request.
 *
 * Validates data for updating an existing role including
 * name uniqueness ignoring the current role.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Modules\Core\Models\Role;

class UpdateRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $role = $this->route('role');

        return Gate::allows('update', $role);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $roleId = $this->route('role')->id ?? null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Role::class)->ignore($roleId),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ];
    }
}
