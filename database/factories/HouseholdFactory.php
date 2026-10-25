<?php

namespace Database\Factories;

use App\Enums\ButlerTone;
use App\Models\Household;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Household>
 */
class HouseholdFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'WG '.fake()->streetName(),
            'passive_aggressive_tone' => ButlerTone::Classic,
        ];
    }
}
