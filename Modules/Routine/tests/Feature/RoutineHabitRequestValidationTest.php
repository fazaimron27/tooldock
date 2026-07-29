<?php

namespace Modules\Routine\Tests\Feature;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as LaravelValidator;
use Modules\Routine\Http\Requests\StoreHabitRequest;
use Modules\Routine\Http\Requests\UpdateHabitRequest;
use Tests\TestCase;

class RoutineHabitRequestValidationTest extends TestCase
{
    public function test_store_request_requires_fields_and_valid_habit_values(): void
    {
        $validData = [
            'name' => 'Read',
            'type' => 'boolean',
            'icon' => 'book',
            'color' => '#123ABC',
            'goal_per_week' => 5,
            'target_value' => 0,
        ];

        $this->assertFalse($this->validator(StoreHabitRequest::class, $validData)->fails());

        $requiredValidator = $this->validator(StoreHabitRequest::class, []);
        $this->assertSame(
            ['name', 'type', 'icon', 'color', 'goal_per_week'],
            array_keys($requiredValidator->errors()->toArray())
        );

        $this->assertTrue($this->validator(StoreHabitRequest::class, [
            ...$validData,
            'color' => '#12345',
        ])->errors()->has('color'));

        $this->assertTrue($this->validator(StoreHabitRequest::class, [
            ...$validData,
            'type' => 'measurable',
        ])->errors()->has('unit'));

        $this->assertTrue($this->validator(StoreHabitRequest::class, [
            ...$validData,
            'target_value' => -1,
        ])->errors()->has('target_value'));
    }

    public function test_update_request_rejects_empty_present_fields_and_invalid_habit_values(): void
    {
        $requiredValidator = $this->validator(UpdateHabitRequest::class, [
            'name' => null,
            'type' => null,
            'icon' => null,
            'color' => null,
            'goal_per_week' => null,
        ]);

        $this->assertSame(
            ['name', 'type', 'icon', 'color', 'goal_per_week'],
            array_keys($requiredValidator->errors()->toArray())
        );

        $this->assertTrue($this->validator(UpdateHabitRequest::class, [
            'color' => '123456',
        ])->errors()->has('color'));

        $this->assertTrue($this->validator(UpdateHabitRequest::class, [
            'type' => 'measurable',
        ])->errors()->has('unit'));

        $this->assertTrue($this->validator(UpdateHabitRequest::class, [
            'target_value' => -0.01,
        ])->errors()->has('target_value'));

        $this->assertFalse($this->validator(UpdateHabitRequest::class, [
            'color' => '#abcdef',
            'target_value' => 0,
        ])->fails());
    }

    /**
     * @param  class-string<FormRequest>  $requestClass
     */
    private function validator(string $requestClass, array $data): LaravelValidator
    {
        return Validator::make($data, (new $requestClass)->rules());
    }
}
