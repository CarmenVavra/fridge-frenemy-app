<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Reads GTIN and expiry date from GS1 2D codes (DataMatrix / QR).
 *
 * Supported: GS1 element strings ("01…17…10…", with FNC1/GS separators or in
 * "(01)…(17)…" notation) and GS1 Digital Link URLs ("https://…/01/…?17=…").
 * Plain EAN/UPC barcodes carry no date and are not GS1 element strings.
 */
final class Gs1Code
{
    private const GS = "\x1D";

    /** Fixed-length application identifiers (length of the value). */
    private const FIXED = [
        '00' => 18, '01' => 14, '02' => 14, '11' => 6, '12' => 6, '13' => 6,
        '15' => 6, '16' => 6, '17' => 6, '20' => 2,
    ];

    /** Variable-length application identifiers, terminated by GS or the end. */
    private const VARIABLE = ['10', '21', '22', '30', '37', '90', '91', '92', '93', '94', '95', '96', '97', '98', '99'];

    private function __construct(
        public readonly string $gtin,
        public readonly ?CarbonImmutable $expiresAt,
    ) {}

    public static function parse(string $raw): ?self
    {
        $raw = trim($raw);

        if ($raw === '' || strlen($raw) > 512 || preg_match('/^\d{6,14}$/', $raw)) {
            return null;
        }

        $fields = preg_match('#^https?://#i', $raw) ? self::fromDigitalLink($raw) : self::fromElementString($raw);
        $gtin = $fields['01'] ?? '';

        if (! preg_match('/^\d{8,14}$/', $gtin)) {
            return null;
        }

        // Use-by (17) wins over best-before (15).
        return new self(self::shortGtin($gtin), self::date($fields['17'] ?? $fields['15'] ?? null));
    }

    /**
     * @return array<int|string, string>
     */
    private static function fromDigitalLink(string $url): array
    {
        $fields = [];
        $segments = array_values(array_filter(explode('/', (string) parse_url($url, PHP_URL_PATH)), fn (string $segment): bool => $segment !== ''));

        // The path may carry a custom prefix; the GS1 part starts at the "01" segment.
        $start = array_search('01', $segments, true);

        if ($start !== false) {
            for ($i = $start; $i + 1 < count($segments); $i += 2) {
                $fields[$segments[$i]] = rawurldecode($segments[$i + 1]);
            }
        }

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        foreach (['15', '17'] as $ai) {
            if (isset($query[$ai]) && is_string($query[$ai])) {
                $fields[$ai] = $query[$ai];
            }
        }

        // Digital Link allows GTIN-8/12/13; normalise to 14 digits like the element string.
        if (isset($fields['01']) && ctype_digit($fields['01'])) {
            $fields['01'] = str_pad($fields['01'], 14, '0', STR_PAD_LEFT);
        }

        return $fields;
    }

    /**
     * @return array<int|string, string>
     */
    private static function fromElementString(string $raw): array
    {
        // Symbology identifier some scanners prepend (e.g. "]d2" for GS1 DataMatrix).
        $raw = (string) preg_replace('/^\][A-Za-z]\d/', '', $raw);

        if (preg_match_all('/\((\d{2,4})\)([^(]*)/', $raw, $matches, PREG_SET_ORDER)) {
            $fields = [];
            foreach ($matches as [, $ai, $value]) {
                $fields[$ai] = trim($value);
            }

            return $fields;
        }

        $fields = [];
        $length = strlen($raw);
        $pos = 0;

        while ($pos < $length) {
            if ($raw[$pos] === self::GS) {
                $pos++;

                continue;
            }

            $ai = substr($raw, $pos, 2);

            if (isset(self::FIXED[$ai])) {
                $fields[$ai] = substr($raw, $pos + 2, self::FIXED[$ai]);
                $pos += 2 + self::FIXED[$ai];
            } elseif (preg_match('/^3[1-6]\d{2}$/', substr($raw, $pos, 4))) {
                $pos += 10; // Net weight, volume and other measures: 4-digit AI + 6 digits.
            } elseif (in_array($ai, self::VARIABLE, true)) {
                $end = strpos($raw, self::GS, $pos);
                $end = $end === false ? $length : $end;
                $fields[$ai] = substr($raw, $pos + 2, $end - $pos - 2);
                $pos = $end;
            } else {
                break; // Unknown AI: keep what has been read so far.
            }
        }

        return $fields;
    }

    /**
     * GS1 dates are YYMMDD; day "00" means the last day of the month.
     */
    private static function date(?string $value): ?CarbonImmutable
    {
        if ($value === null || ! preg_match('/^(\d{2})(\d{2})(\d{2})$/', $value, $m)) {
            return null;
        }

        [$year, $month, $day] = [2000 + (int) $m[1], (int) $m[2], (int) $m[3]];

        if ($month < 1 || $month > 12) {
            return null;
        }

        if ($day === 0) {
            return CarbonImmutable::create($year, $month, 1)?->endOfMonth()->startOfDay();
        }

        return checkdate($month, $day, $year) ? CarbonImmutable::create($year, $month, $day) : null;
    }

    /**
     * GTIN-14 with leading zeros → the EAN-8/EAN-13 printed on the pack (what Open Food Facts uses).
     */
    private static function shortGtin(string $gtin): string
    {
        $gtin = str_pad($gtin, 14, '0', STR_PAD_LEFT);

        return match (true) {
            str_starts_with($gtin, '000000') => substr($gtin, 6),
            str_starts_with($gtin, '0') => substr($gtin, 1),
            default => $gtin,
        };
    }
}
