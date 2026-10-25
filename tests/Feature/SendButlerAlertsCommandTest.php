<?php

namespace Tests\Feature;

use App\Enums\FridgeItemStatus;
use App\Models\FridgeItem;
use App\Models\Household;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SendButlerAlertsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_alerts_for_items_expiring_within_two_days(): void
    {
        $household = Household::factory()->create();
        FridgeItem::factory()->for($household)->expiresIn(-1)->create(['name' => 'Milch']);
        FridgeItem::factory()->for($household)->expiresIn(2)->create(['name' => 'Spinat']);
        FridgeItem::factory()->for($household)->expiresIn(5)->create(['name' => 'Eier']);
        FridgeItem::factory()->for($household)->expiresIn(0)->create(['name' => 'Weg', 'status' => FridgeItemStatus::Trashed]);

        $this->artisan('fridge:send-alerts')->assertSuccessful();

        $this->assertSame(2, $household->butlerAlerts()->count());
        $this->assertSame(['Milch', 'Spinat'], $household->butlerAlerts()->with('fridgeItem')->get()
            ->map(fn ($alert) => $alert->fridgeItem?->name)->sort()->values()->all());
    }

    public function test_command_refreshes_statuses(): void
    {
        $item = FridgeItem::factory()->expiresIn(5)->create();
        $this->assertSame(FridgeItemStatus::Fresh, $item->status);

        Carbon::setTestNow(Carbon::today()->addDays(6));
        $this->artisan('fridge:send-alerts')->assertSuccessful();

        $this->assertSame(FridgeItemStatus::Expired, $item->fresh()?->status);
        Carbon::setTestNow();
    }

    public function test_command_is_scheduled_daily_at_eight(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->filter(fn (Event $event): bool => str_contains((string) $event->command, 'fridge:send-alerts'));

        $this->assertCount(1, $events);
        $this->assertSame('0 8 * * *', $events->first()?->expression);
    }
}
