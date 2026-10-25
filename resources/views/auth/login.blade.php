<x-layouts.guest title="Anmelden" kicker="Willkommen zurück">
    <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-4 px-5">
        @csrf
        <div>
            <label for="email" class="ff-label">E-Mail</label>
            <input id="email" name="email" type="email" class="ff-input" value="{{ old('email') }}" required autofocus autocomplete="username"
                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')<p id="email-error" class="ff-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password" class="ff-label">Passwort</label>
            <input id="password" name="password" type="password" class="ff-input" required autocomplete="current-password"
                   @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
            @error('password')<p id="password-error" class="ff-error">{{ $message }}</p>@enderror
        </div>
        <label class="flex cursor-pointer items-center gap-2.5 text-sm font-semibold">
            <input type="checkbox" name="remember" value="1" class="h-5 w-5 border-2 border-ink accent-accent">
            Angemeldet bleiben
        </label>
        <button type="submit" class="ff-btn-primary">Anmelden</button>
        <div class="flex flex-col gap-2 border-t-2 border-ink pt-4 text-sm font-semibold">
            <a href="{{ route('password.request') }}" class="text-accent-700 underline underline-offset-4 hover:text-accent-600">Passwort vergessen?</a>
            <a href="{{ route('register') }}" class="text-accent-700 underline underline-offset-4 hover:text-accent-600">Noch kein Konto? Registrieren</a>
        </div>
    </form>
</x-layouts.guest>
