<?php

namespace Modules\Newsletter\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\App\Models\User;
use Modules\Newsletter\Models\Campaign;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Newsletter\Models\Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::first()?->id ?? User::factory(),
            'subject' => fake()->sentence(),
            'status' => 'draft',
            'content' => fake()->paragraphs(3, true),
            'selected_posts' => [],
            'scheduled_at' => null,
        ];
    }

    /**
     * Indicate that the campaign should be sent.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
        ]);
    }

    /**
     * Indicate that the campaign should be sending.
     */
    public function sending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sending',
        ]);
    }
}
