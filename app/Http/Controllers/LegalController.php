<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class LegalController extends Controller
{
    public function imprint(): View
    {
        $operator = config('legal.operator');

        return view('legal.imprint', compact('operator'));
    }

    public function privacy(): View
    {
        $operator = config('legal.operator');
        $updated = config('legal.updated');
        $sessionCookie = config('session.cookie');
        $sessionMinutes = config('session.lifetime');
        $usesGemini = filled(config('services.gemini.key'));

        return view('legal.privacy', compact('operator', 'updated', 'sessionCookie', 'sessionMinutes', 'usesGemini'));
    }
}
