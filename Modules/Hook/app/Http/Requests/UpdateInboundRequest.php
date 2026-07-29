<?php

/**
 * UpdateInboundRequest
 *
 * Form request for validating and authorizing inbound webhook endpoint updates.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Hook\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Hook\Http\Controllers\HookInboundController;

/**
 * Class UpdateInboundRequest
 *
 * @property string|null $name
 * @property string|null $description
 * @property bool|null $is_active
 *
 * @see HookInboundController::update()
 */
class UpdateInboundRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('inbound'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
