<?php

namespace App\Http\Controllers;

use App\Http\Requests\DetectExpiryDateRequest;
use App\Http\Requests\ScanBarcodeRequest;
use App\Models\Household;
use App\Services\ProductLookupService;
use App\Support\ExpiryDateParser;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ScanController extends Controller
{
    public function index(): View
    {
        return view('scan.index');
    }

    /**
     * POST /api/scan-barcode – household memory first, then Open Food Facts.
     */
    public function lookup(ScanBarcodeRequest $request, ProductLookupService $products): JsonResponse
    {
        $household = $request->user()?->household;
        abort_unless($household instanceof Household, 403);

        $result = $products->lookup($household, (string) $request->validated('barcode'), $request->codeExpiry());

        if (! $result['found']) {
            return response()->json($result + [
                'message' => 'Unbekannter Barcode. Selbst Open Food Facts weiß nicht, was du da gekauft hast.',
            ], 404);
        }

        return response()->json($result);
    }

    /**
     * POST /api/datum-erkennen – finds the expiry date in text the browser read from the pack (OCR).
     */
    public function detectDate(DetectExpiryDateRequest $request): JsonResponse
    {
        $date = ExpiryDateParser::parse((string) $request->validated('text'));

        return response()->json([
            'found' => $date !== null,
            'expires_at' => $date?->toDateString(),
        ]);
    }
}
