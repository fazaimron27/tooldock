<?php

namespace Modules\Treasury\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Treasury\Models\Budget;

class BudgetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Budget::class;

    /**
     * Define the model's default state.
     *
     * Note: There is a unique constraint on (user_id, category_id).
     * Each user can only have ONE budget per category.
     * When creating multiple budgets in tests, ensure each uses a different category.
     * The category name serves as the budget identifier (no separate 'name' field).
     */
    public function definition(): array
    {
        return [
            'amount' => $this->faker->randomFloat(2, 100000, 5000000),
            'is_active' => true,
            'is_recurring' => true,
            'rollover_enabled' => $this->faker->boolean(30),
        ];
    }

    /**
     * Indicate that the budget is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the budget is not recurring.
     */
    public function oneTime(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_recurring' => false,
        ]);
    }

    /**
     * Indicate that the budget has rollover enabled.
     */
    public function withRollover(): static
    {
        return $this->state(fn (array $attributes) => [
            'rollover_enabled' => true,
        ]);
    }
}
