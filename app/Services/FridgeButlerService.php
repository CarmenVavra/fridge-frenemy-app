<?php

namespace App\Services;

use App\Enums\ButlerTone;
use App\Models\ButlerAlert;
use App\Models\FridgeItem;
use App\Models\Household;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Writes the passive-aggressive butler messages, using Google Gemini when a key is
 * configured and the tone's built-in lines otherwise.
 */
class FridgeButlerService
{
    /**
     * Generate a message for an item.
     *
     * @return array{message: string, source: string}
     */
    public function messageFor(FridgeItem $item, ButlerTone $tone): array
    {
        $days = $item->days_until_expiration;
        $fallback = $tone->quote($item->name, $days) ?? $tone->allClear();

        $generated = $this->askGemini($this->prompt($item->name, $days, $tone));

        return $generated === null
            ? ['message' => $fallback, 'source' => 'fallback']
            : ['message' => $generated, 'source' => 'gemini'];
    }

    /**
     * Create an alert for the household's most urgent item, or an all-clear line.
     */
    public function alertForHousehold(Household $household, ?FridgeItem $item = null): ButlerAlert
    {
        $tone = $household->passive_aggressive_tone;

        $item ??= $household->fridgeItems()->active()->orderBy('expires_at')->first();

        if ($item === null || $item->days_until_expiration > FridgeItem::CRITICAL_DAYS) {
            $payload = ['message' => $tone->allClear(), 'source' => 'fallback'];
        } else {
            $payload = $this->messageFor($item, $tone);
            $item->increment('annoyance_level');
        }

        return $household->butlerAlerts()->create([
            'fridge_item_id' => $item?->id,
            'tone' => $tone,
            'message' => $payload['message'],
            'source' => $payload['source'],
        ]);
    }

    public function prompt(string $name, int $days, ButlerTone $tone): string
    {
        $status = match (true) {
            $days < 0 => 'ist seit '.(-$days).' '.(-$days === 1 ? 'Tag' : 'Tagen').' abgelaufen',
            $days === 0 => 'läuft heute ab',
            $days === 1 => 'läuft morgen ab',
            default => "läuft in {$days} Tagen ab",
        };

        return "Du bist der ultimative, extrem passiv-aggressive Kühlschrank-Butler. Dein Stil: {$tone->persona()}. "
            ."Das Produkt '{$name}' {$status}. Schreibe einen kurzen, maximal 2 Sätze langen, sarkastischen und humorvollen Satz "
            .'auf Deutsch, der den Besitzer beschämt und zum sofortigen Verzehr oder Wegwerfen auffordert. '
            .'Antworte nur mit dem Satz, ohne Anführungszeichen.';
    }

    private function askGemini(string $prompt): ?string
    {
        $key = (string) config('services.gemini.key');

        if ($key === '') {
            return null;
        }

        $url = rtrim((string) config('services.gemini.base_url'), '/')
            .'/models/'.config('services.gemini.model').':generateContent';

        try {
            $response = Http::withHeaders(['x-goog-api-key' => $key])
                ->acceptJson()
                ->timeout((int) config('services.gemini.timeout'))
                ->post($url, [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['temperature' => 1.0, 'maxOutputTokens' => 200],
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Gemini request failed', ['error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Gemini returned an error', ['status' => $response->status()]);

            return null;
        }

        $text = trim((string) $response->json('candidates.0.content.parts.0.text'), " \n\r\t\"„“");

        return $text === '' ? null : Str::limit($text, 400);
    }
}
