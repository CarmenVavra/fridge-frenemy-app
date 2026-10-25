<?php

namespace App\Support;

/**
 * Facial expression and wording of the fridge avatar.
 */
final class FridgeMood
{
    public const HAPPY = 'happy';

    public const MEH = 'meh';

    public const ANGRY = 'angry';

    /**
     * SVG path data for the avatar face, keyed by mood.
     *
     * @return array<string, array{mouth: string, brows: string, fill: string}>
     */
    public static function faces(): array
    {
        return [
            self::HAPPY => ['mouth' => 'M38 76Q50 88 62 76', 'brows' => 'M34 48L46 46M54 46L66 48', 'fill' => '#ffffff'],
            self::MEH => ['mouth' => 'M40 78L60 78', 'brows' => 'M34 49L46 49M54 49L66 49', 'fill' => '#ffffff'],
            self::ANGRY => ['mouth' => 'M38 84Q50 72 62 84', 'brows' => 'M33 46L46 53M67 46L54 53', 'fill' => '#ffc4b8'],
        ];
    }

    /**
     * @return array{mouth: string, brows: string, fill: string}
     */
    public static function face(string $mood): array
    {
        return self::faces()[$mood] ?? self::faces()[self::MEH];
    }

    /**
     * Mood of the "Bilanz" avatar, derived from the rescue ratio (0–1).
     * Without any eaten or thrown-away item (null) there is nothing to judge yet.
     *
     * @return array{mood: string, word: string, line: string}
     */
    public static function forRescueRatio(?float $ratio): array
    {
        return match (true) {
            $ratio === null => ['mood' => self::MEH, 'word' => 'Abwartend', 'line' => 'Noch nichts gerettet, noch nichts verschwendet. Er beobachtet dich.'],
            $ratio >= 0.72 => ['mood' => self::HAPPY, 'word' => 'Stolz', 'line' => 'Er hält dich für fast zurechnungsfähig.'],
            $ratio >= 0.5 => ['mood' => self::MEH, 'word' => 'Skeptisch', 'line' => 'Er hat Notizen gemacht.'],
            default => ['mood' => self::ANGRY, 'word' => 'Wütend', 'line' => 'Er redet nicht mehr mit dir.'],
        };
    }
}
