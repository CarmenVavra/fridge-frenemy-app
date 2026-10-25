<?php

namespace App\Actions;

use App\Enums\FridgeItemStatus;
use App\Models\FridgeItem;
use App\Models\HouseholdStat;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Marks an item as eaten (hero counter) or thrown away (guilt counter).
 */
class ResolveFridgeItem
{
    public function handle(FridgeItem $item, FridgeItemStatus $outcome): HouseholdStat
    {
        if (! $outcome->isResolved()) {
            throw new InvalidArgumentException('Outcome must be consumed or trashed.');
        }

        return DB::transaction(function () use ($item, $outcome): HouseholdStat {
            $item->update(['status' => $outcome]);

            $stats = HouseholdStat::query()->firstOrCreate(['household_id' => $item->household_id]);
            $stats->increment($outcome === FridgeItemStatus::Consumed ? 'saved_count' : 'trashed_count');

            return $stats;
        });
    }
}
