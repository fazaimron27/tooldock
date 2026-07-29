<?php

/**
 * StoreFolioRequest
 *
 * Form request for validating and authorizing resume creation.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Folio\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Folio\Http\Controllers\FolioController;
use Modules\Folio\Models\Folio;

/**
 * Class StoreFolioRequest
 *
 * @property string $name
 *
 * @see FolioController::store()
 */
class StoreFolioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Folio::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
