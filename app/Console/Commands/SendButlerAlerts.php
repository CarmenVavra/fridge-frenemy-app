<?php

namespace App\Console\Commands;

use App\Models\FridgeItem;
use App\Models\Household;
use App\Services\FridgeButlerService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Daily 08:00 job: refreshes item statuses and lets the butler shame every household
 * about items that expire within two days.
 */
class SendButlerAlerts extends Command
{
    /** Upper bound of AI messages per household and day, to keep API costs in check. */
    private const MAX_ALERTS_PER_HOUSEHOLD = 5;

    /**
     * @var string
     */
    protected $signature = 'fridge:send-alerts';

    /**
     * @var string
     */
    protected $description = 'Refresh fridge item statuses and generate the daily passive-aggressive butler alerts';

    public function handle(FridgeButlerService $butler): int
    {
        $alerts = 0;

        Household::query()->chunkById(100, function ($households) use ($butler, &$alerts): void {
            /** @var Household $household */
            foreach ($households as $household) {
                $items = $household->fridgeItems()->active()->orderBy('expires_at')->get();

                $items->each(function (FridgeItem $item): void {
                    $item->syncStatus();
                    $item->saveQuietly();
                });

                $items
                    ->filter(fn (FridgeItem $item): bool => $item->expires_at->lte(Carbon::today()->addDays(FridgeItem::ALERT_DAYS)))
                    ->take(self::MAX_ALERTS_PER_HOUSEHOLD)
                    ->each(function (FridgeItem $item) use ($butler, $household, &$alerts): void {
                        $butler->alertForHousehold($household, $item);
                        $alerts++;
                    });
            }
        });

        $this->info("{$alerts} Butler-Alarm(e) erzeugt.");

        return self::SUCCESS;
    }
}
