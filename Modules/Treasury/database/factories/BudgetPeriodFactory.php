<?php

namespace Modules\Treasury\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Treasury\Models\Budget;
use Modules\Treasury\Models\BudgetPeriod;

class BudgetPeriodFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = BudgetPeriod::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'budget_id' => Budget::factory(),
            'period' => now()->format('Y-m'),
            'amount' => $this->faker->randomFloat(2, 100, 1000),

            'description' => $this->faker->sentence(),
        ];
    }
}
