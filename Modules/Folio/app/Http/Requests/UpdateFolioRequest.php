<?php

/**
 * UpdateFolioRequest
 *
 * Form request for validating and authorizing resume content auto-save.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Folio\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Folio\Http\Controllers\FolioController;

/**
 * Class UpdateFolioRequest
 *
 * @property array $content
 *
 * @see FolioController::update()
 */
class UpdateFolioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $folio = $this->route('folio');

        return $this->user()->can('update', $folio);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'array'],
        ];
    }
}
