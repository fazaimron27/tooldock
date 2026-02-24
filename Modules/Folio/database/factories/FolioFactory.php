<?php

/**
 * Folio Factory
 *
 * Factory for generating Folio model instances in tests.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Folio\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Folio\Models\Folio;

/**
 * Class FolioFactory
 *
 * @extends Factory<Folio>
 */
class FolioFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Folio>
     */
    protected $model = Folio::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [];
    }
}
