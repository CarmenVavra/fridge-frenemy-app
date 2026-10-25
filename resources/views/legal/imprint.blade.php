<x-layouts.guest title="Impressum" kicker="Rechtliches">
    <div class="flex flex-col gap-6 px-5 text-[15px]">
        <section aria-labelledby="operator-title">
            <h2 id="operator-title" class="ff-kicker text-accent-700">Medieninhaber und Betreiber</h2>
            <x-operator-address :operator="$operator" class="mt-2" />
        </section>

        <section aria-labelledby="purpose-title">
            <h2 id="purpose-title" class="ff-kicker text-accent-700">Zweck dieser Website</h2>
            <p class="mt-2">
                Fridge Frenemy ist ein privates, nicht kommerzielles Projekt: eine Web-App, die Haltbarkeiten im Kühlschrank im Blick behält
                und mit passiv-aggressiven Sprüchen gegen Lebensmittelverschwendung arbeitet. Die Nutzung ist kostenlos.
            </p>
            <p class="mt-2 text-sm text-neutral-700">Offenlegung gemäß § 25 Mediengesetz und Angaben gemäß § 5 E-Commerce-Gesetz.</p>
        </section>

        <section aria-labelledby="liability-title">
            <h2 id="liability-title" class="ff-kicker text-accent-700">Haftung</h2>
            <p class="mt-2">
                Die Sprüche des Butlers sind Satire. Die angezeigten Haltbarkeiten sind Hinweise, keine Garantie:
                Ob ein Lebensmittel noch genießbar ist, entscheiden Augen, Nase und gesunder Menschenverstand.
                Produktdaten stammen teilweise aus der offenen Datenbank Open Food Facts und können unvollständig sein.
            </p>
        </section>

        <section aria-labelledby="privacy-link-title">
            <h2 id="privacy-link-title" class="ff-kicker text-accent-700">Datenschutz</h2>
            <p class="mt-2">
                Welche Daten die App speichert und welche Rechte du hast, steht in der
                <a href="{{ route('legal.privacy') }}" class="font-semibold text-accent-700 underline underline-offset-4 hover:text-accent-600">Datenschutzerklärung</a>.
            </p>
        </section>
    </div>
</x-layouts.guest>
