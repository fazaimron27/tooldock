<?php

/**
 * Store Habit Request
 *
 * Validates input for creating a new habit.
 * Supports both boolean and measurable habit types.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Routine\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Routine\Models\Habit;

/**
 * Class StoreHabitRequest
 *
 * @property string $name
 * @property string|null $type
 * @property string|null $icon
 * @property string|null $color
 * @property int|null $goal_per_week
 * @property string|null $unit
 * @property float|null $target_value
 */
class StoreHabitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Habit::class);
    }

    /**
     * Prepare the data for validation.
     *
     * Converts empty strings to null for optional fields to ensure
     * consistent database storage.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'icon' => $this->icon === '' ? null : $this->icon,
            'color' => $this->color === '' ? null : $this->color,
            'unit' => $this->unit === '' ? null : $this->unit,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:boolean,measurable'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'goal_per_week' => ['nullable', 'integer', 'min:1', 'max:7'],
            'unit' => ['nullable', 'required_if:type,measurable', 'string', 'max:50'],
            'target_value' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
