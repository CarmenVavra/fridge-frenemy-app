<?php

namespace App\Http\Controllers;

use App\Actions\ResolveFridgeItem;
use App\Enums\FridgeItemStatus;
use App\Http\Requests\StoreFridgeItemRequest;
use App\Http\Requests\UpdateFridgeItemExpiryRequest;
use App\Models\FridgeItem;
use App\Models\Household;
use App\Services\FridgeDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FridgeController extends Controller
{
    /**
     * The "Fach" dashboard.
     */
    public function index(Request $request, FridgeDashboardService $dashboard): View
    {
        $household = $this->household($request);
        $stats = $household->statsOrCreate();
        $cards = $dashboard->cards($household);
        $alert = $dashboard->pendingAlert($household);

        return view('fridge.index', compact('household', 'stats', 'cards', 'alert'));
    }

    public function store(StoreFridgeItemRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->household($request)->fridgeItems()->create([
            'name' => $data['name'],
            'barcode' => $data['barcode'] ?? null,
            'category' => $data['category'] ?? null,
            'quantity' => $data['quantity'],
            'unit' => $data['unit'] ?? 'Stück',
            'expires_at' => $data['expires_at'],
        ]);

        return redirect()->route('fridge.index')
            ->with('toast', $data['name'].' eingelagert. Ich behalte es im Auge.');
    }

    /**
     * Correct the best-before date, e.g. after a scan kept the +7 days default.
     */
    public function updateExpiry(UpdateFridgeItemExpiryRequest $request, FridgeItem $item): RedirectResponse
    {
        abort_if($item->status->isResolved(), 409, 'Dieser Artikel ist bereits erledigt.');

        $item->update($request->validated());

        return redirect()->route('fridge.index')
            ->with('toast', 'Neues Datum für '.$item->name.' notiert. Ich vergesse nichts.');
    }

    public function consume(Request $request, FridgeItem $item, ResolveFridgeItem $resolve): JsonResponse|RedirectResponse
    {
        return $this->resolve($request, $item, $resolve, FridgeItemStatus::Consumed, 'Gerettet. Der Kühlschrank ist fast stolz.', 'Eins gerettet, :count übrig. Der Kühlschrank zählt mit.');
    }

    public function trash(Request $request, FridgeItem $item, ResolveFridgeItem $resolve): JsonResponse|RedirectResponse
    {
        return $this->resolve($request, $item, $resolve, FridgeItemStatus::Trashed, 'Verbucht. Der Schuld-Counter dankt dir.', 'Eins weg, :count übrig. Der Schuld-Counter dankt dir.');
    }

    /**
     * Books one piece; $partialToast is used while pieces are left (":count" = how many).
     */
    private function resolve(Request $request, FridgeItem $item, ResolveFridgeItem $resolve, FridgeItemStatus $outcome, string $toast, string $partialToast): JsonResponse|RedirectResponse
    {
        abort_if($item->status->isResolved(), 409, 'Dieser Artikel ist bereits erledigt.');

        $stats = $resolve->handle($item, $outcome);
        $remaining = $item->wasChanged('status') ? 0 : $item->quantity;
        $toast = $remaining > 0 ? str_replace(':count', (string) $remaining, $partialToast) : $toast;

        if ($request->expectsJson()) {
            return response()->json([
                'saved' => $stats->saved_count,
                'trashed' => $stats->trashed_count,
                'remaining' => $remaining,
                'meta' => $item->details,
                'toast' => $toast,
            ]);
        }

        return redirect()->route('fridge.index')->with('toast', $toast);
    }

    private function household(Request $request): Household
    {
        $household = $request->user()?->household;
        abort_if($household === null, 403);

        return $household;
    }
}
