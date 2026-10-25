<x-layouts.guest title="Neues Passwort" kicker="Zweite Chance">
    <form method="POST" action="{{ route('password.store') }}" class="flex flex-col gap-4 px-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div>
            <label for="email" class="ff-label">E-Mail</label>
            <input id="email" name="email" type="email" class="ff-input" value="{{ old('email', $email) }}" required autocomplete="username"
                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')<p id="email-error" class="ff-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password" class="ff-label">Neues Passwort</label>
            <input id="password" name="password" type="password" class="ff-input" required autofocus autocomplete="new-password"
                   aria-describedby="password-hint @error('password') password-error @enderror">
            <p id="password-hint" class="mt-1.5 text-xs text-neutral-700">Mindestens 8 Zeichen, Buchstaben und Zahlen.</p>
            @error('password')<p id="password-error" class="ff-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password_confirmation" class="ff-label">Neues Passwort wiederholen</label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="ff-input" required autocomplete="new-password">
        </div>
        <button type="submit" class="ff-btn-primary">Passwort speichern</button>
    </form>
</x-layouts.guest>
