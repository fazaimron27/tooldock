<?php

/**
 * StoreInboundRequest
 *
 * Form request for validating and authorizing inbound webhook endpoint creation.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Hook\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Hook\Http\Controllers\HookInboundController;
use Modules\Hook\Models\HookInbound;

/**
 * Class StoreInboundRequest
 *
 * @see HookInboundController::store()
 */
class StoreInboundRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', HookInbound::class);
    }
}
