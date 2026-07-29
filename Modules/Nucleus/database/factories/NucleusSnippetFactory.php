<?php

/**
 * NucleusSnippet Factory
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Nucleus\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Nucleus\Models\NucleusSnippet;

/**
 * @extends Factory<NucleusSnippet>
 */
class NucleusSnippetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<NucleusSnippet>
     */
    protected $model = NucleusSnippet::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sampleJson = [
            'id' => fake()->uuid(),
            'name' => fake()->name(),
            'email' => fake()->email(),
            'active' => fake()->boolean(),
            'score' => fake()->randomFloat(2, 0, 100),
            'tags' => fake()->words(3),
            'address' => [
                'street' => fake()->streetAddress(),
                'city' => fake()->city(),
                'country' => fake()->countryCode(),
            ],
        ];

        return [
            'title' => fake()->words(3, true),
            'raw_json' => json_encode($sampleJson, JSON_PRETTY_PRINT),
        ];
    }
}
