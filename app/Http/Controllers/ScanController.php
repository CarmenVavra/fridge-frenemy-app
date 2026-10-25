<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScanBarcodeRequest;
use App\Services\OpenFoodFactsService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ScanController extends Controller
{
    public function index(): View
    {
        return view('scan.index');
    }

    /**
     * POST /api/scan-barcode – Open Food Facts lookup for the scanner.
     */
    public function lookup(ScanBarcodeRequest $request, OpenFoodFactsService $openFoodFacts): JsonResponse
    {
        $barcode = (string) $request->validated('barcode');
        $product = $openFoodFacts->lookup($barcode);

        if ($product === null) {
            return response()->json([
                'found' => false,
                'barcode' => $barcode,
                'message' => 'Unbekannter Barcode. Selbst Open Food Facts weiß nicht, was du da gekauft hast.',
            ], 404);
        }

        return response()->json(['found' => true] + $product);
    }
}
