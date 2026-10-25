<?php

namespace Database\Seeders;

use App\Models\Household;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Local demo data matching the UI mockups ("WG Sonnenallee").
 *
 * Login: demo@fridgefrenemy.test / kuehlschrank123
 */
class DemoSeeder extends Seeder
{
    public const EMAIL = 'demo@fridgefrenemy.test';

    public const PASSWORD = 'kuehlschrank123';

    public function run(): void
    {
        if (User::where('email', self::EMAIL)->exists()) {
            return;
        }

        $household = Household::create(['name' => 'WG Sonnenallee', 'invite_code' => 'SONNE123']);
        $household->statsOrCreate()->update(['saved_count' => 12, 'trashed_count' => 4]);

        $user = new User(['name' => 'Demo', 'email' => self::EMAIL, 'password' => self::PASSWORD]);
        $user->household()->associate($household);
        $user->save();

        $items = [
            ['Vollmilch', 1, 'Liter', 'Milchprodukte', -2],
            ['Griechischer Joghurt', 2, 'Becher', 'Milchprodukte', 0],
            ['Gouda, jung', 1, 'Packung', 'Käse', 1],
            ['Babyspinat', 1, 'Beutel', 'Gemüse', 2],
            ['Hähnchenbrust', 500, 'g', 'Fleisch', 3],
            ['Eier', 10, 'Stück', 'Eier', 9],
            ['Butter', 1, 'Stück', 'Milchprodukte', 21],
            ['Dijon-Senf', 1, 'Glas', 'Saucen', 120],
        ];

        foreach ($items as [$name, $quantity, $unit, $category, $days]) {
            $household->fridgeItems()->create([
                'name' => $name,
                'quantity' => $quantity,
                'unit' => $unit,
                'category' => $category,
                'expires_at' => Carbon::today()->addDays($days),
            ]);
        }
    }
}
