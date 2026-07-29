<?php

namespace Modules\Treasury\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Treasury\Models\TreasuryGoal;

class TreasuryGoalFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = TreasuryGoal::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [];
    }
}
