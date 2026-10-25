<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{int, int, string, string}>
     */
    public static function moods(): array
    {
        return [
            'proud' => [12, 4, 'Stolz', '75%'],
            'sceptical' => [5, 5, 'Skeptisch', '50%'],
            'angry' => [1, 9, 'Wütend', '10%'],
        ];
    }

    /**
     * @dataProvider moods
     */
    public function test_balance_shows_counters_and_mood(int $saved, int $trashed, string $mood, string $percent): void
    {
        $household = Household::factory()->create(['name' => 'WG Sonnenallee']);
        $household->statsOrCreate()->update(['saved_count' => $saved, 'trashed_count' => $trashed]);
        $user = User::factory()->for($household)->create();

        $this->actingAs($user)->get(route('stats.index'))
            ->assertOk()
            ->assertSee('WG Sonnenallee')
            ->assertSee('Helden-Counter')
            ->assertSee('Schuld-Counter')
            ->assertSee($mood)
            ->assertSee($percent);
    }

    public function test_balance_is_neutral_before_anything_was_eaten_or_thrown_away(): void
    {
        $household = Household::factory()->create();
        $user = User::factory()->for($household)->create();

        $this->actingAs($user)->get(route('stats.index'))
            ->assertOk()
            ->assertSee('Abwartend')
            ->assertSee('Noch keine Daten')
            ->assertDontSee('Wütend')
            ->assertDontSee('0%');
    }
}
