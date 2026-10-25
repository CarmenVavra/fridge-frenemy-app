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
                Mitbewohner:innen geben diesen Code bei der Registrierung oder hier im Profil unter „Anderem Haushalt beitreten“ ein. Aktuell {{ $household->users_count }} {{ $household->users_count === 1 ? 'Mitglied' : 'Mitglieder' }}.
            </p>
            <form method="POST" action="{{ route('profile.invite-code') }}" class="mt-3">
                @csrf
                <button type="submit" class="ff-btn-secondary" aria-describedby="invite-code-regenerate-hint">Neuen Code erzeugen</button>
                <p id="invite-code-regenerate-hint" class="mt-1.5 text-xs text-neutral-700">Der alte Code funktioniert danach nicht mehr. Wer schon drin ist, bleibt drin.</p>
            </form>
        </div>
    </section>

    {{-- Join another household --}}
    <section class="border-t-2 border-ink px-5 py-5" aria-labelledby="join-title">
        <h2 id="join-title" class="ff-kicker text-accent-700">Anderem Haushalt beitreten</h2>
        <form method="POST" action="{{ route('profile.join') }}" class="mt-3 flex flex-col gap-3">
            @csrf
            <div>
                <label for="join-invite-code" class="ff-label">Einladungscode</label>
                <input id="join-invite-code" name="invite_code" type="text" class="ff-input uppercase tracking-[.12em]" maxlength="12" required autocomplete="off"
                       value="{{ old('invite_code') }}"
                       @error('invite_code') aria-invalid="true" aria-describedby="join-invite-code-error" @enderror>
                @error('invite_code')<p id="join-invite-code-error" class="ff-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="flex items-start gap-2.5 text-[13px] font-medium leading-[1.4]">
                    <input type="checkbox" name="confirm_leave" value="1" required class="mt-0.5 size-5 flex-none accent-accent"
                           @error('confirm_leave') aria-invalid="true" aria-describedby="confirm-leave-error" @enderror>
                    <span>
                        Ich verlasse „{{ $household->name }}“.
                        @if ($household->users_count === 1)
                            <strong class="text-accent-700">Du bist das einzige Mitglied: Dein bisheriger Kühlschrank wird mit allen Artikeln und Zählern gelöscht.</strong>
                        @else
                            Die anderen Mitglieder und alle Artikel bleiben dort.
                        @endif
                    </span>
                </label>
                @error('confirm_leave')<p id="confirm-leave-error" class="ff-error">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="ff-btn-secondary">Beitreten</button>
        </form>
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

    {{-- Delete account --}}
    <section class="border-t-2 border-ink px-5 py-5" aria-labelledby="delete-account-title">
        <h2 id="delete-account-title" class="ff-kicker text-accent-700">Konto löschen</h2>
        <p id="delete-account-hint" class="mt-2 text-[13px] font-medium leading-[1.4]">
            Dein Konto, dein Name und deine E-Mail-Adresse werden endgültig gelöscht. Das lässt sich nicht rückgängig machen.
            @if ($household->users_count === 1)
                <strong class="text-accent-700">Du bist das einzige Mitglied: „{{ $household->name }}“ wird mit allen Artikeln und Zählern gelöscht.</strong>
            @else
                „{{ $household->name }}“ mit allen Artikeln bleibt für die anderen Mitglieder erhalten.
            @endif
        </p>
        <form method="POST" action="{{ route('profile.destroy') }}" class="mt-3 flex flex-col gap-3"
              x-data @submit="if (! confirm('Konto wirklich endgültig löschen?')) $event.preventDefault()">
            @csrf
            @method('DELETE')
            <div>
                <label for="delete-password" class="ff-label">Passwort zur Bestätigung</label>
                <input id="delete-password" name="password" type="password" class="ff-input" required autocomplete="current-password"
                       aria-describedby="delete-account-hint @error('password', 'deleteAccount') delete-password-error @enderror"
                       @error('password', 'deleteAccount') aria-invalid="true" @enderror>
                @error('password', 'deleteAccount')<p id="delete-password-error" class="ff-error">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="ff-btn border-accent-700 bg-transparent text-accent-700 hover:bg-accent/10">
                <x-icon name="trash-2" />Konto endgültig löschen
            </button>
        </form>
    </section>

    <section class="flex flex-col gap-4 border-t-2 border-ink px-5 py-5">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="ff-btn-ink"><x-icon name="log-out" />Abmelden</button>
        </form>
        <x-legal-links />
    </section>
</x-layouts.app>
