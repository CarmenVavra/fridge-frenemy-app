# Fridge Frenemy 🧀

Der passiv-aggressive Kühlschrank-Butler: eine mobile PWA, die Haltbarkeiten überwacht.
Wenn etwas abläuft, beschämt sie den Haushalt mit sarkastischen Sprüchen, auf Wunsch von Google Gemini.

- **Fach**: Aggro-Barometer, Helden- und Schuld-Counter, Filter, Produktkarten mit Gefahrenskala, R.I.P.-Stempel und Schweißtropfen. *Aufgegessen* löst Konfetti und Vibration aus, *Weggeworfen* lässt die Karte wegrutschen.
- **Scan**: Barcode per Kamera (`html5-qrcode`). Browser ohne eingebauten `BarcodeDetector` (Safari auf iPhone/iPad, Firefox) bekommen ihn von `barcode-detector` (zxing-wasm, selbst gehostet unter `/build/zxing`). Bekannte Produkte kommen aus dem eigenen Haushalt (Name und zuletzt eingetragene Haltbarkeit), neue von Open Food Facts. Das Ablaufdatum kommt, in dieser Reihenfolge, aus einem GS1-QR-/DataMatrix-Code (`App\Support\Gs1Code`), aus der Erinnerung oder startet bei +7 Tagen. Per „Datum von der Packung scannen“ liest Tesseract.js (OCR, läuft auf dem Gerät, selbst gehostet unter `/build/tesseract`) das aufgedruckte Datum; `App\Support\ExpiryDateParser` findet darin das Ablaufdatum. Im Fach lässt es sich nachträglich ändern.
- **Butler**: Tonalität wählen (Classic, Italian Mom, Drama Queen, Shakespeare) und den Alarm testen.
- **Bilanz**: Rettungsquote und die Stimmung des Kühlschranks.
- Konten mit Haushalten: WG-Mitglieder treten per Einladungscode bei. Passwort ändern und „Passwort vergessen“ sind eingebaut.

Specs und Design: `produktkonzept_fridge_frenemy.md`, `technisches_konzept.md`, `UI_Mockups/`.

## Stack

Laravel 12 (PHP ≥ 8.2) · Blade · Tailwind CSS 3 · Alpine.js · Vite · SQLite lokal / MySQL in Produktion.

## Lokal starten

```bat
start.bat
```

Das Skript installiert bei Bedarf die Abhängigkeiten, legt `.env` und die SQLite-Datenbank mit Demo-Daten an und startet zwei Server:

- Backend auf **http://localhost:8550**
- Vite-Dev-Server auf Port 5173

`stop.bat` beendet beide.

Den Demo-Login findest du in `database/seeders/DemoSeeder.php`.

## Befehle

| Zweck | Befehl |
| --- | --- |
| Tests | `php artisan test` |
| Statische Analyse | `./vendor/bin/phpstan analyse` |
| Code-Style | `./vendor/bin/pint` |
| Assets bauen | `npm run build` |
| Butler-Alarme erzeugen (läuft täglich 08:00) | `php artisan fridge:send-alerts` |

## Konfiguration (`.env`)

| Variable | Bedeutung |
| --- | --- |
| `GEMINI_API_KEY` | Google-Gemini-Key. Ohne Key nutzt der Butler die eingebauten Sprüche. |
| `GEMINI_MODEL` | Standard: `gemini-2.5-flash` |
| `OPENFOODFACTS_USER_AGENT` | Eigener User-Agent für Open Food Facts (wird von denen verlangt) |

## Deployment

Siehe **[DEPLOYMENT.md](DEPLOYMENT.md)**. Nach der einmaligen Einrichtung reicht `deploy.bat`.

---

## Profi-Tipp für Laravel-Entwickler: "Laravel Boost MCP"

Damit Claude Code noch besser mit Ihrer Datenbank umgehen kann, empfiehlt sich das Open-Source-Paket `laravel/boost`.
Sie installieren es per Composer im Projekt:

```bash
composer require laravel/boost --dev
```

Das Paket registriert sich als sogenannter Model Context Protocol (MCP) Server.
Dadurch kann Claude Code direkt mit den Internals Ihrer Laravel-App arbeiten und zum Beispiel:

- live Ihre echte lokale Datenbank-Struktur abfragen, ohne die Dateien lesen zu müssen,
- Artisan-Tinker-Befehle im Hintergrund ausführen, um Daten direkt zu prüfen.
