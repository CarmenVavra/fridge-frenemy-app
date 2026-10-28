<?php

namespace App\Services;

use App\Models\ButlerAlert;
use App\Models\FridgeItem;
use App\Models\Household;
use Illuminate\Support\Carbon;

/**
 * Builds the view data for the "Fach" dashboard.
 */
class FridgeDashboardService
{
    /**
     * Active items of the household as plain arrays, sorted by days left.
     *
     * @return list<array{id: string, name: string, meta: string, quantity: int, days: int, stage: int, quote: string|null, consume_url: string, trash_url: string}>
     */
    public function cards(Household $household): array
    {
        $tone = $household->passive_aggressive_tone;

        return $household->fridgeItems()
            ->active()
            ->orderBy('expires_at')
            ->orderBy('name')
            ->get()
            ->map(fn (FridgeItem $item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'meta' => $item->details,
                'quantity' => $item->quantity,
                'days' => $item->days_until_expiration,
                'stage' => $item->urgency_stage,
                'expires_on' => $item->expires_at->toDateString(),
                'quote' => $tone->quote($item->name, $item->days_until_expiration),
                'consume_url' => route('fridge.consume', $item),
                'trash_url' => route('fridge.trash', $item),
                'expiry_url' => route('fridge.expiry', $item),
            ])
            ->values()
            ->all();
    }

    /**
     * Ids of the household's active items with this barcode, soonest expiry first.
     *
     * @return list<string>
     */
    public function idsForBarcode(Household $household, string $barcode): array
    {
        return $household->fridgeItems()
            ->active()
            ->where('barcode', $barcode)
            ->orderBy('expires_at')
            ->pluck('id')
            ->map(fn (mixed $id): string => (string) $id)
            ->values()
            ->all();
    }

    /**
     * The most urgent undismissed alert of today, shown as the push banner.
     */
    public function pendingAlert(Household $household): ?ButlerAlert
    {
        return $household->butlerAlerts()
            ->whereNull('dismissed_at')
            ->where('created_at', '>=', Carbon::today())
            ->latest()
            ->first();
    }
}
