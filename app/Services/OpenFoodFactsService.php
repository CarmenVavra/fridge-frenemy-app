<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Looks up product names and categories by barcode via the Open Food Facts v2 API.
 */
class OpenFoodFactsService
{
    /**
     * @return array{barcode: string, name: string, category: string}|null
     */
    public function lookup(string $barcode): ?array
    {
        try {
            $response = Http::withHeaders(['User-Agent' => (string) config('services.openfoodfacts.user_agent')])
                ->acceptJson()
                ->timeout((int) config('services.openfoodfacts.timeout'))
                ->get(rtrim((string) config('services.openfoodfacts.base_url'), '/').'/product/'.rawurlencode($barcode).'.json', [
                    'lc' => 'de',
                    'fields' => 'product_name,product_name_de,generic_name,brands,categories',
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Open Food Facts lookup failed', ['barcode' => $barcode, 'error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful() || (int) $response->json('status') !== 1) {
            return null;
        }

        $product = (array) $response->json('product', []);
        $name = $this->firstFilled($product, ['product_name_de', 'product_name', 'generic_name']);

        if ($name === null) {
            return null;
        }

        return [
            'barcode' => $barcode,
            'name' => Str::limit($name, 120, ''),
            'category' => $this->category($product['categories'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $product
     * @param  list<string>  $keys
     */
    private function firstFilled(array $product, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = trim((string) ($product[$key] ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * Open Food Facts lists categories from generic to specific; the last one is the most useful.
     */
    private function category(mixed $categories): string
    {
        $parts = array_values(array_filter(array_map(
            fn (string $part): string => trim((string) preg_replace('/^[a-z]{2}:/', '', trim($part))),
            explode(',', (string) $categories)
        )));

        return $parts === [] ? 'Sonstiges' : Str::limit(end($parts), 60, '');
    }
}
