<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateHouseholdRequest;
use App\Http\Requests\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $household = $user?->household()->withCount('users')->firstOrFail();

        return view('profile.edit', compact('user', 'household'));
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()?->update(['password' => $request->validated('password')]);

        return back()->with('toast', 'Passwort geändert.');
    }

    public function updateHousehold(UpdateHouseholdRequest $request): RedirectResponse
    {
        $request->user()?->household?->update(['name' => $request->validated('name')]);

        return back()->with('toast', 'Haushalt umbenannt. Der Kühlschrank bleibt derselbe.');
    }
}
