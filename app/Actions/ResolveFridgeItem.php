<?php

namespace App\Actions;

use App\Enums\FridgeItemStatus;
use App\Models\FridgeItem;
use App\Models\HouseholdStat;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Marks one piece of an item as eaten (hero counter) or thrown away (guilt
 * counter). With more than one piece left only the quantity goes down; the
 * last piece resolves the item.
 */
class ResolveFridgeItem
{
    public function handle(FridgeItem $item, FridgeItemStatus $outcome): HouseholdStat
    {
        if (! $outcome->isResolved()) {
            throw new InvalidArgumentException('Outcome must be consumed or trashed.');
        }

        return DB::transaction(function () use ($item, $outcome): HouseholdStat {
            if ($item->quantity > 1) {
                $item->decrement('quantity');
            } else {
                $item->update(['status' => $outcome]);
            }

            $stats = HouseholdStat::query()->firstOrCreate(['household_id' => $item->household_id]);
            $stats->increment($outcome === FridgeItemStatus::Consumed ? 'saved_count' : 'trashed_count');

            return $stats;
        });
    }
}
