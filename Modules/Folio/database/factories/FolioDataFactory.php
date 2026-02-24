<?php

/**
 * Folio Data Factory
 *
 * Factory for generating FolioData model instances in tests.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Folio\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Folio\Models\FolioData;

/**
 * Class FolioDataFactory
 *
 * @extends Factory<FolioData>
 */
class FolioDataFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<FolioData>
     */
    protected $model = FolioData::class;

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
