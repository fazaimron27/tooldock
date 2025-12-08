<?php

namespace Modules\Vault\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\User;
use Modules\Vault\Models\Vault;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Vault\Models\Vault>
 */
class VaultFactory extends Factory
{
    protected $model = Vault::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['login', 'card', 'note', 'server'];
        $type = fake()->randomElement($types);

        return [
            'user_id' => User::factory(),
            'category_id' => null,
            'type' => $type,
            'name' => fake()->words(2, true),
            'username' => fake()->optional()->userName(),
            'email' => fake()->optional()->email(),
            'issuer' => fake()->optional()->company(),
            'value' => fake()->optional()->password(),
            'totp_secret' => fake()->optional()->regexify('[A-Z2-7]{32}'),
            'fields' => null,
            'url' => fake()->optional()->url(),
            'is_favorite' => fake()->boolean(20),
        ];
    }

    /**
     * Indicate that the vault should be a favorite.
     */
    public function favorite(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_favorite' => true,
        ]);
    }

    /**
     * Indicate that the vault should be of a specific type.
     */
    public function ofType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }

    /**
     * Indicate that the vault should have a TOTP secret.
     */
    public function withTotp(): static
    {
        return $this->state(fn (array $attributes) => [
            'totp_secret' => fake()->regexify('[A-Z2-7]{32}'),
        ]);
    }
}
