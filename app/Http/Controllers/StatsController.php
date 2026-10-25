<?php

namespace App\Http\Controllers;

use App\Support\FridgeMood;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatsController extends Controller
{
    /**
     * The "Bilanz" screen: hero/guilt counters and the avatar's mood.
     */
    public function index(Request $request): View
    {
        $household = $request->user()?->household;
        abort_if($household === null, 403);

        $stats = $household->statsOrCreate();
        $ratio = $stats->rescueRatio();
        $mood = FridgeMood::forRescueRatio($ratio);
        $percent = (int) round($ratio * 100);

        return view('stats.index', compact('household', 'stats', 'mood', 'percent'));
    }
}
