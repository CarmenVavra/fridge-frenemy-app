<x-layouts.guest title="Datenschutzerklärung" kicker="Rechtliches">
    <div class="flex flex-col gap-6 px-5 text-[15px] [&_li]:mt-1 [&_p]:mt-2 [&_ul]:mt-2 [&_ul]:list-disc [&_ul]:pl-5">
        <p class="!mt-0">
            Kurz gesagt: Fridge Frenemy speichert nur, was die App zum Funktionieren braucht. Kein Tracking, keine Werbung,
            keine Analyse-Tools, keine Weitergabe zu Werbezwecken. Kamerabilder verlassen dein Gerät nicht.
        </p>

        <section aria-labelledby="controller-title">
            <h2 class="ff-kicker text-accent-700" id="controller-title">1. Verantwortlich</h2>
            <x-operator-address :operator="$operator" class="mt-2" />
        </section>

        <section aria-labelledby="account-title">
            <h2 class="ff-kicker text-accent-700" id="account-title">2. Konto und Haushalt</h2>
            <p>Wenn du dich registrierst und die App nutzt, speichern wir:</p>
            <ul>
                <li>deinen Namen, deine E-Mail-Adresse und dein Passwort (nur als verschlüsselter Hash, nie im Klartext),</li>
                <li>deinen Haushalt: Name, Einladungscode, gewählte Tonalität des Butlers und die Mitglieder,</li>
                <li>die eingetragenen Lebensmittel: Name, Barcode, Kategorie, Ablaufdatum und ob sie gegessen oder weggeworfen wurden,</li>
                <li>die Nachrichten des Butlers und die Bilanz deines Haushalts.</li>
            </ul>
            <p>
                Alle Mitglieder eines Haushalts sehen dessen Lebensmittel, Nachrichten und Bilanz sowie die Namen der anderen Mitglieder.
            </p>
            <p>
                Rechtsgrundlage ist die Bereitstellung der App, die du nutzen möchtest (Art. 6 Abs. 1 lit. b DSGVO).
                Die Daten bleiben gespeichert, solange dein Konto besteht. Verlässt das letzte Mitglied einen Haushalt,
                wird der Haushalt samt Inhalt gelöscht.
            </p>
        </section>

        <section aria-labelledby="camera-title">
            <h2 class="ff-kicker text-accent-700" id="camera-title">3. Kamera, Barcode und Datums-Scan</h2>
            <p>
                Die Kamera wird nur geöffnet, wenn du einen Scan startest. Barcode-Erkennung und Texterkennung (OCR) laufen
                vollständig in deinem Browser. Es werden keine Bilder oder Videos übertragen oder gespeichert,
                an unseren Server geht nur die erkannte Ziffernfolge bzw. der erkannte Datumstext.
            </p>
            <p>
                Für unbekannte Barcodes fragt unser Server die freie Produktdatenbank
                <a href="https://world.openfoodfacts.org" class="font-semibold text-accent-700 underline underline-offset-4 hover:text-accent-600" rel="noopener">Open Food Facts</a>
                (Open Food Facts, Frankreich) nach dem Produktnamen. Dabei wird ausschließlich der Barcode übermittelt,
                keine Daten über dich. Die Anfrage kommt von unserem Server, nicht von deinem Gerät.
            </p>
        </section>

        @if ($usesGemini)
            <section aria-labelledby="gemini-title">
                <h2 class="ff-kicker text-accent-700" id="gemini-title">4. Sprüche des Butlers (Google Gemini)</h2>
                <p>
                    Die Sprüche des Butlers schreibt der KI-Dienst Google Gemini (Google). Unser Server übermittelt dafür nur
                    den Produktnamen, die verbleibenden Tage und die gewählte Tonalität. Name, E-Mail-Adresse oder andere Daten
                    über dich werden nicht übermittelt. Rechtsgrundlage ist unser berechtigtes Interesse an einer unterhaltsamen App
                    (Art. 6 Abs. 1 lit. f DSGVO).
                </p>
            </section>
        @endif

        <section aria-labelledby="cookies-title">
            <h2 class="ff-kicker text-accent-700" id="cookies-title">{{ $usesGemini ? 5 : 4 }}. Cookies und Speicher im Browser</h2>
            <p>Die App setzt nur technisch notwendige Cookies, deshalb gibt es kein Cookie-Banner:</p>
            <ul>
                <li><strong>{{ $sessionCookie }}</strong>: hält dich angemeldet, gültig {{ $sessionMinutes }} Minuten nach der letzten Aktivität.</li>
                <li><strong>XSRF-TOKEN</strong>: schützt Formulare vor Missbrauch (CSRF-Schutz), gleiche Laufzeit.</li>
                <li><strong>remember_web_…</strong>: nur wenn du „Angemeldet bleiben“ wählst; gilt, bis du dich abmeldest (höchstens 400 Tage).</li>
            </ul>
            <p>
                Damit die App installierbar ist und schnell lädt, legt ein Service Worker die Programmdateien (Code, Schrift, Symbole)
                im Speicher deines Browsers ab. Dort liegen keine persönlichen Daten.
            </p>
            <p>Rechtsgrundlage ist § 165 Abs. 3 TKG 2021 und unser berechtigtes Interesse an einer sicheren App (Art. 6 Abs. 1 lit. f DSGVO).</p>
        </section>

        <section aria-labelledby="hosting-title">
            <h2 class="ff-kicker text-accent-700" id="hosting-title">{{ $usesGemini ? 6 : 5 }}. Hosting, Server-Protokolle und E-Mail</h2>
            <p>
                Die App läuft auf Servern der netcup GmbH, Daimlerstraße 25, 76185 Karlsruhe, Deutschland, die als
                Auftragsverarbeiter für uns tätig ist. Beim Aufruf der App verarbeitet der Server technisch notwendig deine IP-Adresse,
                Zeitpunkt, aufgerufene Adresse und Browser-Kennung. Das dient dem sicheren Betrieb und der Abwehr von Angriffen
                (Art. 6 Abs. 1 lit. f DSGVO). Zu deiner Anmeldung speichern wir zusätzlich IP-Adresse und Browser-Kennung,
                bis die Sitzung abläuft. Server-Protokolle werden nach kurzer Zeit automatisch gelöscht.
            </p>
            <p>
                E-Mails, etwa der Link zum Zurücksetzen des Passworts, verschicken wir über den Mailserver von netcup.
                Der Link ist 60 Minuten gültig.
            </p>
            <p>Schriften und alle anderen Dateien der App werden von unserem eigenen Server geladen, nicht von Google oder anderen Anbietern.</p>
        </section>

        <section aria-labelledby="rights-title">
            <h2 class="ff-kicker text-accent-700" id="rights-title">{{ $usesGemini ? 7 : 6 }}. Deine Rechte</h2>
            <p>
                Du hast das Recht auf Auskunft, Berichtigung, Löschung, Einschränkung der Verarbeitung, Datenübertragbarkeit
                und Widerspruch. Schreib dafür einfach eine E-Mail an die oben genannte Adresse, zum Beispiel wenn du dein Konto
                löschen lassen möchtest.
            </p>
            <p>
                Wenn du meinst, dass wir deine Daten nicht korrekt verarbeiten, kannst du dich bei der österreichischen
                Datenschutzbehörde beschweren: Barichgasse 40–42, 1030 Wien,
                <a href="https://www.dsb.gv.at" class="font-semibold text-accent-700 underline underline-offset-4 hover:text-accent-600" rel="noopener">www.dsb.gv.at</a>.
            </p>
        </section>

        <p class="text-sm text-neutral-700">Stand: {{ $updated }}</p>
    </div>
</x-layouts.guest>
