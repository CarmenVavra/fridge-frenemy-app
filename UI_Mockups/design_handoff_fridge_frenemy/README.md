# Handoff: Fridge Frenemy (Passive-Aggressive Kühlschrank-Butler) — Mobile App

## Overview
Mobile PWA, die Haltbarkeiten überwacht und per KI (Gemini 2.0 Flash) sarkastische Warnungen verschickt. Nutzer: Einzelpersonen, WGs, Familien. Sprache der UI: **Deutsch**. Vier Tabs: **Fach** (Kühlschrank-Dashboard), **Scan** (Einlagern), **Butler** (Tonalität + Push-Test), **Bilanz** (Counter + Avatar).

Fachliche Specs liegen in `specs/` (`produktkonzept_fridge_frenemy.md`, `technisches_konzept.md`: Laravel 11, Blade + Tailwind + Alpine.js, Open Food Facts v2, Gemini, `html5-qrcode`, `navigator.vibrate`, `canvas-confetti`). Dieses Dokument beschreibt nur das visuelle/Interaktions-Design.

## About the Design Files
`prototype/Fridge Frenemy.dc.html` ist eine **HTML-Design-Referenz** (Prototyp mit Look & Verhalten), **kein Produktionscode**. Aufgabe: im Ziel-Stack (laut Spec Blade + Tailwind + Alpine.js) nachbauen. Die Datei ist ein "Design Component" (Template + kleine JS-Klasse) und braucht eine Laufzeit (`support.js`), die nicht enthalten ist — zum Ansehen die Screenshots nutzen, zum Auslesen von Markup/Logik die Datei als Text lesen. Alle Texte, Styles und Logik stehen darin inline.

## Fidelity
**High-fidelity.** Finale Farben, Typografie, Abstände und Interaktionen. Pixelgenau nachbauen; Tokens in Tailwind-Config abbilden (siehe unten). Daten und Butler-Sprüche im Prototyp sind Mock/Festtexte — in Produktion kommen die Sprüche von Gemini (Prompt siehe Produktkonzept §3), `Component.TONES` zeigt die Fallback-Texte.

## Design-Sprache (Modernist)
Flach, architektonisch, **Radius 0** überall (außer dem Gerätrahmen im Mockup, der nicht Teil der App ist), **2px-Linien** (`#201e1d`) als Gliederung, sichtbares Raster, alles linksbündig (auch Button-Labels), nur Archivo, ein einziges Rot. Icons: **Lucide** (Stroke 2, round caps).

**Abweichung vom technischen Konzept:** Die Aggro-Level (Grün/Gelb/Orange/Rot) sind NICHT als Ampelfarben umgesetzt, sondern in Ink + Rot (5er-Balkenskala; Barometer = Surface / Rot-200 / Rot-Vollfläche). Falls Ampelfarben gewünscht sind, mit dem Designer klären.

## Design Tokens
Farben
- bg `#f3f2f2` · surface `#eae9e9` · text/ink `#201e1d` · divider = ink (2px, im Prototyp voll deckend)
- accent `#ec3013` · accent-200 `#ffe0d9` · accent-300 `#ffc4b8` · accent-600 `#dd2b0f` · accent-700 `#ae1800` (Fließtext in Rot immer 700)
- neutral-300 `#d7d3d3` · -400 `#bab6b6` · -600 `#7d7979` · -700 `#605d5d` · -800 `#444141`
- Weiß `#ffffff` nur auf Ink/Rot-Flächen.

Typografie: **Archivo** (Heading 800, Body 400/500/600/700).
- Display (Seitentitel): 34px/1, ls −0.02em, 800
- Barometer-Wort: 38px/.95, ls −0.02em, 800, uppercase
- Counter-Zahl (Bilanz): 64px/1, ls −0.03em, 800 · Counter klein (Fach): 28px/1
- Karten-Tage-Zahl: 34px/1, ls −0.03em, 800
- Kartentitel 19px/1.1, 800 · Body 13–15px · Meta 12px
- Kicker/Labels: 11px, 700, ls .12–.14em, uppercase
- Tab-Label 11px, 700, ls .06em, uppercase
Spacing: 4/8/12/16/24/32; Seitenrand 20px. Schatten nur Toast/Push: `0 12px 32px rgba(45,43,43,.22)`.

## Screens

Rahmen: 390×844 (iPhone-Format). Statusleiste 46px. Scrollbereich `flex:1`. Tab-Bar unten: 4 Spalten, `border-top: 2px ink`, Padding unten 18px (Home-Indicator). Aktiver Tab: 4px roter Balken oben, Label ink; inaktiv `neutral-600`. Tabs: Fach (refrigerator), Scan (scan-barcode), Butler (bell), Bilanz (bar-chart-3).

### 1 · Fach (`screenshots/01-fach-dashboard.png`)
- **Header** (20px Rand): „FRIDGE FRENEMY" 15px/800, ls .12em uppercase; rechts Haushaltsname 12px/600 neutral-700.
- **Aggro-Barometer** (Vollbreite, `border-y 2px`, Padding 16/20/18): links Kicker „Aggro-Barometer" + Wort + Zeile (14px/500); rechts Kühlschrank-Avatar 72×72 (SVG, Stroke 3.5 currentColor: Körper-Rechteck 60×88, Trennlinie bei y=34, Griffe, Augen r=4, Brauen + Mund je nach Stimmung). Unten Skala 3 Segmente (6px hoch, gap 4px), aktive Segmente voll, inaktive 20% Deckkraft.
  - Entspannt (nichts ≤3 Tage): bg surface, ink, Avatar fröhlich. Zeile „Nichts läuft ab. Ich traue dem Frieden nicht."
  - Genervt (min. Artikel ≤3 Tage): bg accent-200, ink, Avatar neutral. „{n} Artikel laufen in 2–3 Tagen ab. Ich zähle mit."
  - Sauer (Artikel ≤0 Tage): bg accent `#ec3013`, weiß, **Puls** (bg ↔ accent-600, 1.2s ease-in-out infinite), Avatar wütend (Füllung transparent) mit Zitter-Animation (translateX ±2px, .4s). „{n} Artikel laufen heute ab oder sind schon hinüber."
- **Counter-Zeile** (2 Spalten, Trenner 2px, `border-bottom 2px`): „Gerettet" (neutral-700) / „Schuld" (accent-700, Zahl `accent-700`), Zahl 28px/800.
- **Filter** (3 gleiche Spalten, `border-bottom 2px`): „Alle n / Kritisch n / Frisch n" (13px/700, Padding 12px). Aktiv: ink-Fläche + weißer Text. Kritisch = ≤3 Tage; Frisch = >3.
- **Produktkarte** (Grid 76px | 1fr, `border-bottom 2px`, Tap = auf-/zuklappen):
  - Links: Tage-Zahl (34px; ≥Stufe 4 in accent, sonst ink), darunter Label 10px/700 uppercase neutral-700: „Tage"/„Tag"/„Heute"/„Überfällig"; Überfällig als „−2".
  - Rechts: Name; rechts daneben **Gefahrenskala** 5 Balken à 7×12px, gap 2px (gefüllt: ink, ab Stufe 4 accent; leer neutral-300). Meta „{menge} {einheit} · {kategorie}" 12px/500 neutral-700. Butler-Spruch 13px/500/1.38, accent-700, in „…" (nur Tage ≤3).
  - Stufen: >7 Tage=1, 4–7=2, 2–3=3, 0–1=4, <0=5.
  - **Kritisch (0–1 Tag):** Lucide `droplets` (22px, accent) unter dem Label, Zitter-Animation (.5s infinite) = „Schweißtropfen".
  - **Abgelaufen:** Inhalt 55% Deckkraft, bg surface, **R.I.P.-Stempel**: 3px accent-Rahmen, accent-Text, Archivo 900 22px, ls .14em, Padding 2/10, `rotate(-12deg)`, bg `rgba(243,242,242,.7)`, absolut rechts 18px / oben 38px; Einblend-Animation .35s (scale 1.8→1, opacity 0→1). ⚠ Stempel überlappt aktuell den Spruchtext — in der Umsetzung Textbreite begrenzen oder Stempel höher setzen.
  - **Aktionsleiste** (sichtbar bei Tage ≤1 oder nach Tap; 2 Spalten, `border-bottom 2px`, Padding 14/20, 14px/700, Icon 18px + Label links, gap 10): „Aufgegessen" (Lucide `utensils`, ink-Fläche, hover neutral-800) | „Weggeworfen" (Lucide `trash-2`, accent-Fläche, hover accent-600).
- Leerzustand: „Nichts zu sehen. / Verdächtig ordentlich." 22px/800 (zweite Zeile neutral-600).
- Sortierung: aufsteigend nach Resttagen.

### 2 · Scan (`03-scan-sucher.png`, `04-scan-treffer.png`)
- Titel: Kicker „Einlagern", Titel „Neuer Verdächtiger".
- **Sucher:** 350×230 Ink-Fläche (Rand 20px), vier weiße L-Ecken 28×28 / 3px, Hinweis „Barcode im Rahmen platzieren" 12px/600 uppercase neutral-400. Während „scanning": „Suche Barcode …" + roter 3px-Scanbalken (Glow), Animation 176px hoch/runter .7s alternate.
- Buttons (volle Breite, linksbündig): „Barcode scannen" (primary, Icon scan-barcode), „Manuell eintragen" (secondary). Fußnote 12px/1.5 neutral-700.
- **Treffer-Formular:** Leiste „TREFFER · OPEN FOOD FACTS" + EAN rechts (surface, `border-y 2px`; nur bei Scan). Felder: „Produkt" (Text-Input, Design-System `.input`), „Menge" (Stepper −/n/+, 44px-Zellen, Rahmen 2px ink, hover accent-200, min 1), „Haltbar bis" (Stepper in Tagen, Default **+7**, min 0, Anzeige „30. Okt."). Butler-Kommentar 13px/500 accent-700 abhängig von Tagen (≤2 „Das ist kein Einlagern, das ist Aufschieben.", ≥14 „Optimistisch. Das gefällt mir.", sonst „Sieben Tage. Ich setze mir schon mal eine Erinnerung."). Buttons: „Einlagern" (primary, Icon check; deaktiviert 45% wenn Name leer), „Abbrechen".
- Einlagern → Wechsel zu Fach, Filter „Alle", Toast „{Name} eingelagert. Ich behalte es im Auge."

### 3 · Butler (`05-butler-tonalitaet.png`, `06-push-benachrichtigung.png`)
- Titel „Butler-Tonalität", Kicker „Täglich 08:00 Uhr".
- 4 Zeilen (je `border-top 2px`, Padding 14/20, Radio-Quadrat 20px mit 2px-Rahmen, innen 10px-Quadrat): Classic, Italian Mom, Drama Queen, Shakespeare (Enum `classic | italian_mom | dramaqueen | shakespeare`). Name 18px/800 + Beispielspruch 13px/500. Aktiv: ink-Fläche, weißer Text, rotes inneres Quadrat. Wechsel ändert sofort alle Sprüche in Fach/Push.
- Button „Alarm testen" (primary, Icon bell) + Erklärung 12px.
- **Push-Banner** (Overlay oben, 10px Rand, Ink-Fläche, weiß, Padding 14/16): 36×36 roter Kasten mit weißer Glocke; Kopf „KÜHLSCHRANK-BUTLER" / „08:00" 11px/700 uppercase neutral-400; Text 14px/1.35. Einlauf: translateY(−130%)→0, .4s cubic-bezier(.2,.9,.3,1). Auto-Ausblenden nach 6s, Tap schließt. Text = Spruch zum ältesten Artikel.

### 4 · Bilanz (`07-bilanz.png`)
- Titel „Bilanz", Kicker = Haushaltsname.
- Avatar-Zelle (140px breit, 104px-Avatar, `border-right 2px`, bg surface; bei „wütend" accent-200) + Stimmung: Kicker „Stimmung", Wort 26px/800 uppercase, Zeile 13px/500. Quote = gerettet/(gerettet+weggeworfen): ≥72% **Stolz** („Er hält dich für fast zurechnungsfähig."), ≥50% **Skeptisch** („Er hat Notizen gemacht."), sonst **Wütend** („Er redet nicht mehr mit dir.").
- Zwei Counter (2 Spalten, `border-y 2px`): „Helden-Counter" (ink, „Artikel gerettet"), „Schuld-Counter" (Zahl accent, „Opfer der Vergesslichkeit").
- „Rettungsquote" + % und Balken 14px: ink-Anteil = gerettet, Rest accent; Beschriftung „Gerettet" / „Verschwendet". Breite-Transition .4s.

## Interaktionen & Verhalten
- **Aufgegessen:** Konfetti-Burst (≈90 Partikel, Farben `#ec3013 #201e1d #ffc4b8 #ff563c #fff #7d7979`, Start an Tap-Position, Gravitation .3/Frame, Fade nach 90 Frames) + `navigator.vibrate([100,50,100])` + Karte skaliert auf .9 & blendet aus (.45s ease-in) → entfernt, Helden-Counter +1, Toast „Gerettet. Der Kühlschrank ist fast stolz." (Produktion: `canvas-confetti`.)
- **Weggeworfen:** Karte rutscht `translateY(130%)` + Fade (.45s ease-in) → entfernt, Schuld-Counter +1, Toast „Verbucht. Der Schuld-Counter dankt dir."
- Toast: Ink-Fläche, weiß, 14px/600, Padding 12/16, über Tab-Bar (bottom 96px), Einblend .25s, 2.4s sichtbar.
- Scan-Simulation: 1.3s „scanning", dann zufälliger Treffer (Produktion: `html5-qrcode` → `POST /api/scan-barcode` → Open Food Facts v2).
- Hover: Buttons dunkeln eine Ramp-Stufe ab; Stepper-Zellen accent-200. Fokus: `outline: 2px solid #ec3013; offset 2px`. Disabled: 45% Deckkraft.

## State Management
`tab`, `filter (all|crit|fresh)`, `tone`, `items[] {id, name, qty, unit, cat, days}`, `saved`, `trashed`, `open{id}` (aufgeklappte Karten), `leaving{id: eat|trash}`, `scan {phase idle|scanning|found, code, name, cat, qty, shelf(days), fromScan}`, `toast`, `push`. Abgeleitet: Stufe je Artikel, Barometer-Level (min. Resttage), Counts, Quote. Datenmodell/Status (`fresh|warning|expired|consumed|trashed`) laut `specs/technisches_konzept.md` §2; `days` entspricht `expires_at − today`.

## Mock-Daten (Startzustand)
Vollmilch −2 · Griechischer Joghurt 0 · Gouda jung 1 · Babyspinat 2 · Hähnchenbrust 3 · Eier 9 · Butter 21 · Dijon-Senf 120. Counter 12 gerettet / 4 Schuld. Haushalt „WG Sonnenallee".

## Assets
Keine Bitmaps. Icons Lucide (inline SVG): refrigerator, scan-barcode, bell, bar-chart-3, utensils, trash-2, droplets, plus, minus, check. Kühlschrank-Avatar = eigenes SVG im Prototyp. Schrift Archivo (Google Fonts).

## Files
- `prototype/Fridge Frenemy.dc.html` — kompletter Prototyp (Markup, Styles inline, Logik, Butler-Texte)
- `prototype/_ds/…/styles.css`, `readme.md` — Modernist-Design-System (Tokens, `.btn`, `.input`, `.field`)
- `specs/produktkonzept_fridge_frenemy.md`, `specs/technisches_konzept.md` — Produkt- und Tech-Spec
- `screenshots/` — Referenzansichten (2×, 780×1688): `01-fach-dashboard`, `03-scan-sucher`, `04-scan-treffer`, `05-butler-tonalitaet`, `06-push-benachrichtigung`, `07-bilanz`
