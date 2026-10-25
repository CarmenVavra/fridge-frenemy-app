<x-layouts.guest title="Registrieren" kicker="Neuer Haushalt">
    <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-4 px-5" x-data="{ join: @js(filled(old('invite_code'))) }">
        @csrf
        <div>
            <label for="name" class="ff-label">Dein Name</label>
            <input id="name" name="name" type="text" class="ff-input" value="{{ old('name') }}" required autofocus autocomplete="name"
                   @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
            @error('name')<p id="name-error" class="ff-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="email" class="ff-label">E-Mail</label>
            <input id="email" name="email" type="email" class="ff-input" value="{{ old('email') }}" required autocomplete="username"
                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')<p id="email-error" class="ff-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password" class="ff-label">Passwort</label>
            <input id="password" name="password" type="password" class="ff-input" required autocomplete="new-password"
                   aria-describedby="password-hint @error('password') password-error @enderror">
            <p id="password-hint" class="mt-1.5 text-xs text-neutral-700">Mindestens 8 Zeichen, Buchstaben und Zahlen.</p>
            @error('password')<p id="password-error" class="ff-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password_confirmation" class="ff-label">Passwort wiederholen</label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="ff-input" required autocomplete="new-password">
        </div>

        <fieldset class="border-t-2 border-ink pt-4">
            <legend class="ff-label float-left w-full">Haushalt</legend>
            <div class="clear-both grid grid-cols-2 border-2 border-ink" role="group" aria-label="Haushalt wählen">
                <button type="button" class="border-r-2 border-ink py-3 text-[13px] font-bold" @click="join = false"
                        :aria-pressed="(!join).toString()" :class="!join ? 'bg-ink text-white' : 'hover:bg-ink/5'">Neu anlegen</button>
                <button type="button" class="py-3 text-[13px] font-bold" @click="join = true"
                        :aria-pressed="join.toString()" :class="join ? 'bg-ink text-white' : 'hover:bg-ink/5'">Beitreten</button>
            </div>
            <div class="mt-3" x-show="!join">
                <label for="household_name" class="ff-label">Name des Haushalts</label>
                <input id="household_name" name="household_name" type="text" class="ff-input" value="{{ old('household_name') }}"
                       placeholder="z. B. WG Sonnenallee" maxlength="80" :disabled="join"
                       @error('household_name') aria-invalid="true" aria-describedby="household-name-error" @enderror>
                @error('household_name')<p id="household-name-error" class="ff-error">{{ $message }}</p>@enderror
            </div>
            <div class="mt-3" x-show="join" x-cloak>
                <label for="invite_code" class="ff-label">Einladungscode</label>
                <input id="invite_code" name="invite_code" type="text" class="ff-input uppercase tracking-[.12em]" value="{{ old('invite_code') }}"
                       maxlength="12" autocomplete="off" :disabled="!join"
                       @error('invite_code') aria-invalid="true" aria-describedby="invite-code-error" @enderror>
                @error('invite_code')<p id="invite-code-error" class="ff-error">{{ $message }}</p>@enderror
            </div>
        </fieldset>

        <p class="text-[13px] text-neutral-700">
            Was mit deinen Daten passiert, steht in der
            <a href="{{ route('legal.privacy') }}" class="font-semibold text-accent-700 underline underline-offset-4 hover:text-accent-600">Datenschutzerklärung</a>.
        </p>
        <button type="submit" class="ff-btn-primary">Konto anlegen</button>
        <p class="border-t-2 border-ink pt-4 text-sm font-semibold">
            <a href="{{ route('login') }}" class="text-accent-700 underline underline-offset-4 hover:text-accent-600">Schon registriert? Anmelden</a>
        </p>
    </form>
</x-layouts.guest>
