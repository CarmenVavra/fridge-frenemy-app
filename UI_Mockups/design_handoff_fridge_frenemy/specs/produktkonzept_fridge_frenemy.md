# 🧀 Der Passive-Aggressive Kühlschrank-Butler
*Produktkonzept & UI/UX Spezifikation für eine unterhaltsame PWA-App*

---

## 📌 Executive Summary
Der **Passive-Aggressive Kühlschrank-Butler** kombiniert Alltagsnutzen mit humorvollem "Shaming". Die Anwendung unterstützt Personen und WGs dabei, Lebensmittelverschwendung drastisch zu reduzieren. Anstelle trockener Warnungen nutzt die App künstliche Intelligenz (Google Gemini), um hochgradig sarkastische, tagesaktuelle Benachrichtigungen über ablaufende Lebensmittel zu verschicken.

---

## 🏗️ 1. Architektur & Datenfluss

```
[ Barcode Scanner ] ──> [ Open Food Facts API ] ──> [ Laravel DB ]
                                                          │
                                                    (Täglicher Cronjob)
                                                          │
                                                          ▼
[ Smartphone App ] <── [ Push Notification ] <── [ Google Gemini AI ]
```

1. **Erfassung:** Ein Scan des Barcodes holt via **Open Food Facts API** sofort Produktnamen & Kategorie.
2. **Überwachung:** Laravel verwaltet die Haltbarkeit und berechnet tagesaktuell die "Gefahrenstufe".
3. **Mahnwache:** Der Scheduler prüft morgens ablaufende Artikel und schickt Prompts an **Google Gemini 2.0 Flash**.
4. **Interaktion:** Der Nutzer reagiert via Smartphone-App (*Aufgegessen* vs. *Weggeworfen*).

---

## 🎨 2. UI/UX Design & Interaktionskonzept

### A. Das Aggro-Barometer (Header)
Ein emotionaler Zustandskopf oben im Dashboard:
* **Entspannt (Grün):** Keine ablaufenden Produkte.
* **Genervt (Gelb):** Produkte laufen in 2–3 Tagen ab.
* **Sauer / Panik (Rot + Pulse):** Produkte laufen HEUTE ab oder sind bereits verfallen.

### B. Visuelle Produkt-Zustände
* **Kritisch (24h):** Produkt-Icon erhält Schweißtropfen-Overlay 😰.
* **Abgelaufen:** Die Produktkarte wird leicht ausgegraut und erhält einen roten **"R.I.P."**-Retro-Stempel im 12°-Winkel.

### C. Haptisches & Audiovisuelles Feedback
* **Gerettet 🍽️:** Löst eine bunte **Konfetti-Animation** aus + doppeltes Vibrations-Feedback + Erhöhung des *Helden-Counters*.
* **Weggeworfen 🗑️:** Die Karte rutscht nach unten weg + Erhöhung des unbarmherzigen *Schuld-Counters*.

---

## 🤖 3. KI-Prompts (Google Gemini Integration)

**System-Prompt Grundgerüst:**
> *"Du bist der ultimative, extrem passiv-aggressive Kühlschrank-Butler. Das Produkt '{ItemName}' {StatusContext}. Schreibe einen kurzen, maximal 2 Sätze langen, sarkastischen und humorvollen Satz, der den Besitzer beschämt und zum sofortigen Verzehr oder Wegwerfen auffordert."*

### Beispiele für Tonalitäten:
* **Classic:** *„Die Milch ist seit 2 Tagen abgelaufen. Ich hoffe, du hast eine gute Krankenversicherung.“*
* **Italian Mom:** *„Hast du den Joghurt gekauft, nur um ihn verfaulen zu lassen? Wie dein Vater damals!“*
* **Shakespeare:** *„O weh! Welch trauriges Schicksal erleidet dieser Käse im dunklen Verlies deines Gemüsekachs.“*

---

## 📊 4. Gamification & Schuld-Counter

| Metrik | Beschreibung | Auswirkung |
| :--- | :--- | :--- |
| **Gerettete Artikel** | Lebensmittel, die rechtzeitig als *Aufgegessen* markiert wurden. | Kühlschrank-Avatar schaut stolz & zufrieden. |
| **Opfer der Vergesslichkeit** | Artikel, die im Mülleimer gelandet sind. | Kühlschrank-Avatar wird zunehmend wütender. |

---

*Konzept bereit für Design & Entwicklung.*