<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Finds the expiry date in text read from a package by OCR.
 *
 * Packages often print production and expiry date side by side, and OCR
 * output is noisy. So: collect every plausible date between today and
 * ten years ahead, prefer clearly separated dates over digit runs, and
 * return the latest one (the expiry date comes after the production date).
 */
final class ExpiryDateParser
{
    private const SEP = '\s?[.\/\-]\s?';

    public static function parse(string $text, ?CarbonImmutable $today = null): ?CarbonImmutable
    {
        $today ??= CarbonImmutable::today();
        $text = self::normalise($text);

        $separated = [
            // 03.11.2026, 03.11.26, 3/11/26, 03-11-2026
            '/(?<!\d)(?<d>\d{1,2})'.self::SEP.'(?<m>\d{1,2})'.self::SEP.'(?<y>\d{4}|\d{2})(?!\d)/',
            // 18.11.20264711: OCR glued the lot number to a four-digit year
            '/(?<!\d)(?<d>\d{1,2})'.self::SEP.'(?<m>\d{1,2})'.self::SEP.'(?<y>20\d{2})(?=\d)/',
            // 2026-11-03
            '/(?<!\d)(?<y>\d{4})-(?<m>\d{1,2})-(?<d>\d{1,2})(?!\d)/',
            // 03 11 26 (dot-matrix print where OCR lost the dots)
            '/(?<!\d)(?<d>\d{2}) (?<m>\d{2}) (?<y>\d{4}|\d{2})(?!\d)/',
            // 11.2026, 11/2026: month and year only → end of month
            '/(?<![\d.\/\-])(?<m>\d{1,2})[.\/](?<y>\d{4})(?![\d.\/\-])/',
        ];
        $compact = [
            // 03112026, 031126
            '/(?<!\d)(?<d>\d{2})(?<m>\d{2})(?<y>20\d{2}|\d{2})(?!\d)/',
        ];

        return self::latest($text, $separated, $today) ?? self::latest($text, $compact, $today);
    }

    /**
     * Common OCR mix-ups between letters and digits, and unify separators.
     */
    private static function normalise(string $text): string
    {
        $text = strtr($text, ['O' => '0', 'o' => '0', 'D' => '0', 'I' => '1', 'l' => '1', '|' => '1', 'S' => '5', 'B' => '8', ',' => '.']);

        return (string) preg_replace('/[ \t]+/', ' ', $text);
    }

    /**
     * @param  list<string>  $patterns
     */
    private static function latest(string $text, array $patterns, CarbonImmutable $today): ?CarbonImmutable
    {
        $latest = null;
        $limit = $today->addYears(10);

        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $date = self::toDate($match);

                if ($date !== null && $date->gte($today) && $date->lt($limit) && ($latest === null || $date->gt($latest))) {
                    $latest = $date;
                }
            }
        }

        return $latest;
    }

    /**
     * @param  array<int|string, string>  $match
     */
    private static function toDate(array $match): ?CarbonImmutable
    {
        $year = (int) $match['y'];
        $year = $year < 100 ? 2000 + $year : $year;
        $month = (int) $match['m'];

        if (($match['d'] ?? '') === '') {
            return $month >= 1 && $month <= 12
                ? CarbonImmutable::create($year, $month, 1)?->endOfMonth()->startOfDay()
                : null;
        }

        $day = (int) $match['d'];

        return checkdate($month, $day, $year) ? CarbonImmutable::create($year, $month, $day) : null;
    }
}
