# Technical Specification & Design Prompt: Passive-Aggressive Fridge Butler

## 1. Project Overview & System Architecture
- **Product Name:** Passive-Aggressive Fridge Butler (Der Kühlschrank-Butler)
- **Target Audience:** Private individuals, shared flats (WGs), families who want to reduce food waste with a touch of dark humor.
- **Tech Stack:**
  - **Backend:** Laravel 11 / PHP 8.3+
  - **Database:** PostgreSQL / SQLite / MySQL
  - **Frontend:** Laravel Blade + Tailwind CSS + Alpine.js (PWA Setup)
  - **External APIs:** 
    - Open Food Facts REST API v2 (Product scanning)
    - Google Gemini 2.0 Flash API (Generative sarcastic alerts)
  - **Client APIs:** HTML5 Camera API (`html5-qrcode`), Web Vibration API (`navigator.vibrate`), Canvas Confetti.

---

## 2. Database Schema (Laravel Migrations Blueprint)

### `households`
- `id`: `uuid` (Primary Key)
- `name`: `string`
- `passive_aggressive_tone`: `enum('classic', 'italian_mom', 'dramaqueen', 'shakespeare')` - default: `classic`
- `created_at`, `updated_at`: `timestamps`

### `fridge_items`
- `id`: `uuid` (Primary Key)
- `household_id`: `foreignUuid('households')`
- `barcode`: `string`, nullable
- `name`: `string`
- `quantity`: `integer` - default: 1
- `unit`: `string` - default: 'Stück'
- `expires_at`: `date`
- `status`: `enum('fresh', 'warning', 'expired', 'consumed', 'trashed')`
- `annoyance_level`: `integer` - default: 0
- `created_at`, `updated_at`: `timestamps`

### `household_stats`
- `household_id`: `foreignUuid('households')`
- `saved_count`: `integer` - default: 0
- `trashed_count`: `integer` - default: 0

---

## 3. Core Technical Workflows

### A. Barcode Scanning & Open Food Facts Lookup
1. Mobile camera activates using `html5-qrcode`.
2. Decoded barcode is sent via POST to `/api/scan-barcode`.
3. Laravel queries `https://world.openfoodfacts.org/api/v2/product/{barcode}.json` with custom User-Agent.
4. Auto-fills item `name`, `category`, and suggests expiration date (+7 days default).

### B. Daily Cronjob & Gemini AI Generation
1. Scheduled task runs daily at 08:00 AM: `Schedule::command('fridge:send-alerts')->dailyAt('08:00');`.
2. Queries items where `expires_at <= now()->addDays(2)` and status is active.
3. Triggers `FridgeButlerService`, sending a prompt to `Gemini::generativeModel('gemini-2.0-flash')`.
4. Gemini prompt parameters:
   - Item Name
   - Expiration Status (days left / overdue)
   - Selected Household Tone
5. Output cached or dispatched as Web Push / Dashboard Notification.

---

## 4. UI/UX & Design Guidelines for Claude Design

### Color Palette & Theme:
- **Background:** Dark Mode by default (Deep slate/gray `bg-slate-900`) or clean light gray (`bg-slate-50`).
- **Aggro Levels:**
  - Level 1 (Safe): Emerald Green (`#10B981`)
  - Level 2 (Notice): Amber Yellow (`#F59E0B`)
  - Level 3 (Urgent): Orange (`#F97316`)
  - Level 4/5 (Biohazard/Expired): Crimson Red (`#EF4444`) with subtle pulse animation.

### Interactive Micro-Interactions:
- **RIP Stamp:** Applied to expired items (`transform -rotate-12 border-2 border-red-600 text-red-600 font-black uppercase`).
- **Sweat Drops:** Added to items expiring in 24 hours.
- **Consume Action (🍽️):** Triggers `canvas-confetti` + success vibration (`navigator.vibrate([100, 50, 100])`).
- **Trash Action (🗑️):** Slide-down CSS exit animation + increments `trashed_count` in Guilt Counter.

---

## 5. Implementation Instructions for Claude Code

1. Install `google-gemini-php/laravel` and set `GEMINI_API_KEY` in `.env`.
2. Create `FridgeItem` Model with Accessors for `days_until_expiration` and `urgency_stage`.
3. Build `OpenFoodFactsService` using Laravel `Http::withHeaders()`.
4. Create `FridgeButlerService` wrapping the Gemini API call.
5. Create artisan command `fridge:send-alerts` and register in `routes/console.php`.
6. Implement Blade templates with Tailwind CSS, Alpine.js, and HTML5 Scanner Integration.