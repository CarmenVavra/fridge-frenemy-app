<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database. Demo data is never seeded in production.
     */
    public function run(): void
    {
        if (! app()->isProduction()) {
            $this->call(DemoSeeder::class);
        }
    }
}
