<?php

namespace App\Services;

use App\Models\Household;
use Carbon\CarbonImmutable;

/**
 * Answers "what did I just scan?" for the scanner.
 *
 * The household's own history comes first: a product it stored before is
 * filled in with the name it used and the shelf life it entered last time.
 * Only unknown barcodes go to Open Food Facts.
 */
class ProductLookupService
{
    public function __construct(private readonly OpenFoodFactsService $openFoodFacts) {}

    /**
     * @return array{found: bool, barcode: string, name?: string, category?: string, source?: string, remembered_days: int|null, expires_at: string|null}
     */
    public function lookup(Household $household, string $barcode, ?CarbonImmutable $codeExpiry = null): array
    {
        $result = [
            'barcode' => $barcode,
            'remembered_days' => null,
            'expires_at' => $codeExpiry?->toDateString(),
        ];

        $previous = $household->fridgeItems()
            ->where('barcode', $barcode)
            ->latest()
            ->first();

        if ($previous !== null) {
            $storedOn = ($previous->created_at ?? $previous->expires_at)->copy()->startOfDay();
            $days = (int) round($storedOn->diffInDays($previous->expires_at));

            return [
                'found' => true,
                'name' => $previous->name,
                'category' => $previous->category,
                'source' => 'memory',
                'remembered_days' => max(1, $days),
            ] + $result;
        }

        $product = $this->openFoodFacts->lookup($barcode);

        if ($product === null) {
            return ['found' => false] + $result;
        }

        return ['found' => true, 'source' => 'openfoodfacts'] + $product + $result;
    }
}
