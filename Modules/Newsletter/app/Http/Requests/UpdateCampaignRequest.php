<?php

namespace Modules\Newsletter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCampaignRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'selected_posts' => ['required', 'array', 'min:1'],
            'selected_posts.*' => [
                'required',
                Rule::exists('posts', 'id')->where('user_id', $this->user()->id),
            ],
            'scheduled_at' => ['nullable', 'date'],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
