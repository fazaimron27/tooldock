<?php

namespace Modules\Treasury\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Treasury\Models\Transaction::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [];
    }
}
