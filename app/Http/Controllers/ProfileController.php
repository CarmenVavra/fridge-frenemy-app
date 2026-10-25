<?php

namespace App\Http\Controllers;

use App\Actions\JoinHousehold;
use App\Http\Requests\JoinHouseholdRequest;
use App\Http\Requests\UpdateHouseholdRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Models\User;
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

    public function regenerateInviteCode(Request $request): RedirectResponse
    {
        $request->user()?->household?->regenerateInviteCode();

        return back()->with('toast', 'Neuer Code. Der alte ist ab sofort wertlos.');
    }

    public function joinHousehold(JoinHouseholdRequest $request, JoinHousehold $join): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $household = $join->handle($user, $request->validated('invite_code'));

        return redirect()->route('fridge.index')
            ->with('toast', 'Willkommen bei '.$household->name.'. Neuer Kühlschrank, neue Vorwürfe.');
    }
}
