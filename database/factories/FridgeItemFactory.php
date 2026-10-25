<?php

namespace Database\Factories;

use App\Models\FridgeItem;
use App\Models\Household;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<FridgeItem>
 */
class FridgeItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'name' => fake()->randomElement(['Vollmilch', 'Joghurt', 'Gouda', 'Babyspinat', 'Eier', 'Butter']),
            'category' => 'Milchprodukte',
            'quantity' => 1,
            'unit' => 'Stück',
            'expires_at' => Carbon::today()->addDays(fake()->numberBetween(1, 20)),
        ];
    }

    public function expiresIn(int $days): static
    {
        return $this->state(fn (): array => ['expires_at' => Carbon::today()->addDays($days)]);
    }
}
