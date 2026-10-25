<?php

namespace App\Http\Controllers;

use App\Enums\ButlerTone;
use App\Http\Requests\UpdateToneRequest;
use App\Models\ButlerAlert;
use App\Models\Household;
use App\Services\FridgeButlerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ButlerController extends Controller
{
    public function index(Request $request): View
    {
        $household = $this->household($request);
        $tones = ButlerTone::cases();

        return view('butler.index', compact('household', 'tones'));
    }

    public function updateTone(UpdateToneRequest $request): RedirectResponse
    {
        $tone = ButlerTone::from((string) $request->validated('tone'));
        $this->household($request)->update(['passive_aggressive_tone' => $tone]);

        return redirect()->route('butler.index')
            ->with('toast', 'Tonalität: '.$tone->label().'. Der Butler räuspert sich.');
    }

    /**
     * "Alarm testen": generates the alert for the most urgent item right now.
     */
    public function testAlert(Request $request, FridgeButlerService $butler): JsonResponse
    {
        $alert = $butler->alertForHousehold($this->household($request));
        $alert->update(['dismissed_at' => now()]);

        return response()->json([
            'message' => $alert->message,
            'time' => $alert->created_at->format('H:i'),
        ]);
    }

    public function dismissAlert(ButlerAlert $alert): Response
    {
        $alert->update(['dismissed_at' => now()]);

        return response()->noContent();
    }

    private function household(Request $request): Household
    {
        $household = $request->user()?->household;
        abort_if($household === null, 403);

        return $household;
    }
}
