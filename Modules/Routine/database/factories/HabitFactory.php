<?php

/**
 * Habit Factory
 *
 * Factory for generating test Habit model instances.
 * Supports boolean and measurable habit type states.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Routine\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Routine\Models\Habit;

/**
 * Class HabitFactory
 *
 * @extends Factory<Habit>
 */
class HabitFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Habit>
     */
    protected $model = Habit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $colors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];
        $icons = ['dumbbell', 'book', 'brain', 'glass-water', 'footprints', 'target', 'pencil', 'salad'];

        return [
            'name' => fake()->randomElement([
                'Morning Exercise',
                'Read 30 Minutes',
                'Meditate',
                'Drink 8 Glasses of Water',
                'Write Journal',
                'No Social Media',
                'Practice Coding',
                'Healthy Eating',
            ]),
            'type' => 'boolean',
            'icon' => fake()->randomElement($icons),
            'color' => fake()->randomElement($colors),
            'goal_per_week' => fake()->numberBetween(3, 7),
            'status' => 'active',
        ];
    }

    /**
     * Indicate that the habit is measurable.
     *
     * @return static
     */
    public function measurable(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'measurable',
            'name' => fake()->randomElement([
                'Coding',
                'Reading',
                'Running',
                'Sleep',
                'Deep Work',
            ]),
            'unit' => fake()->randomElement(['menit', 'jam', 'km', 'pages']),
            'target_value' => fake()->randomElement([30, 60, 120, 5, 7]),
        ]);
    }

    /**
     * Indicate that the habit is paused.
     *
     * @return static
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paused',
            'paused_at' => now()->subDays(fake()->numberBetween(1, 14)),
            'streak_at_pause' => fake()->numberBetween(0, 30),
        ]);
    }

    /**
     * Indicate that the habit is archived.
     *
     * @return static
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
        ]);
    }
}
