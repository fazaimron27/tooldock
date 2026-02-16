<?php

namespace Modules\Treasury\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Treasury\Models\Wallet;

class WalletFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Wallet::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Main Wallet', 'Savings', 'Emergency Fund', 'Daily Cash', 'Bank Account']),
            'type' => fake()->randomElement(['cash', 'bank', 'ewallet', 'savings']),
            'balance' => fake()->randomFloat(2, 0, 10000),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the wallet is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set the wallet type to cash.
     */
    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'cash',
        ]);
    }

    /**
     * Set the wallet type to bank.
     */
    public function bank(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'bank',
        ]);
    }

    /**
     * Set the wallet type to e-wallet.
     */
    public function ewallet(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'ewallet',
        ]);
    }
}
