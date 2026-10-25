<x-layouts.app title="Profil">
    <div class="px-5 pt-3">
        <a href="{{ route('fridge.index') }}" class="inline-flex items-center gap-1 text-[13px] font-bold text-neutral-700 hover:text-ink">
            <x-icon name="chevron-left" :size="16" />Zurück zum Fach
        </a>
    </div>
    <x-page-title :kicker="$user->email" :title="$user->name" />

    {{-- Household --}}
    <section class="border-t-2 border-ink px-5 py-5" aria-labelledby="household-title">
        <h2 id="household-title" class="ff-kicker text-accent-700">Haushalt</h2>
        <form method="POST" action="{{ route('profile.household') }}" class="mt-3 flex flex-col gap-3">
            @csrf
            @method('PUT')
            <div>
                <label for="household-name" class="ff-label">Name</label>
                <input id="household-name" name="name" type="text" class="ff-input" maxlength="80" required
                       value="{{ old('name', $household->name) }}"
                       @error('name') aria-invalid="true" aria-describedby="household-name-error" @enderror>
                @error('name')<p id="household-name-error" class="ff-error">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="ff-btn-secondary">Haushalt umbenennen</button>
        </form>

        <div class="mt-5 border-2 border-ink bg-surface px-4 py-3">
            <p class="text-xs font-bold uppercase tracking-[.08em]">Einladungscode</p>
            <p class="mt-1 text-[28px] font-extrabold tracking-[.12em]">{{ $household->invite_code }}</p>
            <p class="mt-1 text-[13px] font-medium text-neutral-700">
                Mitbewohner:innen geben diesen Code bei der Registrierung ein. Aktuell {{ $household->users_count }} {{ $household->users_count === 1 ? 'Mitglied' : 'Mitglieder' }}.
            </p>
        </div>
    </section>

    {{-- Password --}}
    <section class="border-t-2 border-ink px-5 py-5" aria-labelledby="password-title">
        <h2 id="password-title" class="ff-kicker text-accent-700">Passwort ändern</h2>
        <form method="POST" action="{{ route('profile.password') }}" class="mt-3 flex flex-col gap-3">
            @csrf
            @method('PUT')
            <div>
                <label for="current_password" class="ff-label">Aktuelles Passwort</label>
                <input id="current_password" name="current_password" type="password" class="ff-input" required autocomplete="current-password"
                       @error('current_password') aria-invalid="true" aria-describedby="current-password-error" @enderror>
                @error('current_password')<p id="current-password-error" class="ff-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="ff-label">Neues Passwort</label>
                <input id="password" name="password" type="password" class="ff-input" required autocomplete="new-password"
                       aria-describedby="password-hint @error('password') password-error @enderror">
                <p id="password-hint" class="mt-1.5 text-xs text-neutral-700">Mindestens 8 Zeichen, Buchstaben und Zahlen.</p>
                @error('password')<p id="password-error" class="ff-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="ff-label">Neues Passwort wiederholen</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="ff-input" required autocomplete="new-password">
            </div>
            <button type="submit" class="ff-btn-secondary">Passwort speichern</button>
        </form>
    </section>

    <section class="border-t-2 border-ink px-5 py-5">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="ff-btn-ink"><x-icon name="log-out" />Abmelden</button>
        </form>
    </section>
</x-layouts.app>
