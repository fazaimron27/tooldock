<?php

/**
 * Profile Update Request.
 *
 * Validates profile update data including name and email
 * with uniqueness constraints for the current user.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Models\User;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'avatar_id' => [
                'nullable',
                'string',
                Rule::when(
                    fn ($input) => ! empty($input['avatar_id']),
                    ['exists:media_files,id']
                ),
            ],
        ];
    }
}
