<?php

namespace App\Http\Controllers\Auth;

use App\Actions\RegisterUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request, RegisterUser $registerUser): RedirectResponse
    {
        /** @var array{name: string, email: string, password: string, household_name?: string|null, invite_code?: string|null} $data */
        $data = $request->validated();
        $user = $registerUser->handle($data);

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('fridge.index')
            ->with('toast', 'Willkommen. Ich beobachte ab jetzt deinen Kühlschrank.');
    }
}
