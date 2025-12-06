<?php

namespace Modules\Groups\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Modules\Core\App\Constants\Roles;
use Modules\Core\App\Models\Role;
use Modules\Groups\Models\Group;

class StoreGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', Group::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Validates group creation data and prevents assignment of the Super Admin role.
     * Uses static caching to avoid repeated database queries during validation.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        static $superAdminRoleId = null;
        if ($superAdminRoleId === null) {
            $superAdminRoleId = Role::where('name', Roles::SUPER_ADMIN)->value('id');
        }

        return [
            'name' => ['required', 'string', 'max:255', 'unique:groups,name'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:groups,slug'],
            'description' => ['nullable', 'string'],
            'members' => ['nullable', 'array'],
            'members.*' => ['exists:'.(config('permission.table_names.users') ?? 'users').',id'],
            'roles' => ['nullable', 'array'],
            'roles.*' => [
                'exists:roles,id',
                function ($attribute, $value, $fail) use ($superAdminRoleId) {
                    if ($superAdminRoleId && (string) $value === (string) $superAdminRoleId) {
                        $fail('The Super Admin role cannot be assigned to groups.');
                    }
                },
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ];
    }
}
