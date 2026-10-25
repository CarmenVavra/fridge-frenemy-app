<x-layouts.guest title="Passwort vergessen" kicker="Kommt in den besten Haushalten vor">
    <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-4 px-5">
        @csrf
        <p class="text-sm font-medium leading-normal text-neutral-800">
            Gib deine E-Mail-Adresse ein. Wir schicken dir einen Link, mit dem du ein neues Passwort setzen kannst.
        </p>
        <div>
            <label for="email" class="ff-label">E-Mail</label>
            <input id="email" name="email" type="email" class="ff-input" value="{{ old('email') }}" required autofocus autocomplete="username"
                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')<p id="email-error" class="ff-error">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="ff-btn-primary">Link anfordern</button>
        <p class="border-t-2 border-ink pt-4 text-sm font-semibold">
            <a href="{{ route('login') }}" class="text-accent-700 underline underline-offset-4 hover:text-accent-600">Zurück zur Anmeldung</a>
        </p>
    </form>
</x-layouts.guest>
