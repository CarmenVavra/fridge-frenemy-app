<?php

namespace Tests\Unit;

use App\Support\ExpiryDateParser;
use App\Support\Gs1Code;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ExpiryCodeParsingTest extends TestCase
{
    /**
     * @return array<string, array{string, string|null}>
     */
    public static function ocrTexts(): array
    {
        return [
            'full date' => ['MHD 03.11.2026', '2026-11-03'],
            'short year' => ['03.11.26', '2026-11-03'],
            'slashes and spaces' => ['3 / 11 / 26', '2026-11-03'],
            'iso' => ['2026-11-18', '2026-11-18'],
            'dots lost' => ['18 11 26', '2026-11-18'],
            'month and year' => ['11/2027', '2027-11-30'],
            'compact' => ['181126', '2026-11-18'],
            'production and expiry' => ["12.10.26\n18.11.26", '2026-11-18'],
            'letters misread' => ['O3.1I.2O26', '2026-11-03'],
            'lot number glued to year' => ['18.11.20264711', '2026-11-18'],
            'past date only' => ['03.10.26', null],
            'impossible date' => ['31.02.27', null],
            'too far ahead' => ['03.11.2040', null],
            'no date' => ['L4711 08', null],
        ];
    }

    #[DataProvider('ocrTexts')]
    public function test_expiry_dates_are_found_in_ocr_text(string $text, ?string $expected): void
    {
        $date = ExpiryDateParser::parse($text, CarbonImmutable::parse('2026-10-25'));

        $this->assertSame($expected, $date?->toDateString());
    }

    /**
     * @return array<string, array{string, string|null, string|null}>
     */
    public static function gs1Codes(): array
    {
        return [
            'datamatrix with FNC1' => ["\x1D".'010400840040222217261103'.'10ABC'."\x1D".'21XYZ', '4008400402222', '2026-11-03'],
            'variable field first' => ["\x1D".'10ABC12'."\x1D".'0104008400402222'.'17261103', '4008400402222', '2026-11-03'],
            'symbology identifier' => [']d2010400840040222215261118', '4008400402222', '2026-11-18'],
            'net weight before date' => ['0104008400402222310300025017261103', '4008400402222', '2026-11-03'],
            'brackets' => ['(01)04008400402222(17)261103(10)L1', '4008400402222', '2026-11-03'],
            'day 00 is end of month' => ['0104008400402222'.'17261100', '4008400402222', '2026-11-30'],
            'no date' => ['010400840040222210ABC', '4008400402222', null],
            'gtin-8' => ['0100000040123455', '40123455', null],
            'digital link' => ['https://example.com/01/4008400402222/10/L1?17=261103', '4008400402222', '2026-11-03'],
            'digital link with prefix' => ['https://brand.example/qr/01/09506000134352?15=270101', '9506000134352', '2027-01-01'],
        ];
    }

    #[DataProvider('gs1Codes')]
    public function test_gs1_codes_are_parsed(string $raw, string $gtin, ?string $expiry): void
    {
        $code = Gs1Code::parse($raw);

        $this->assertNotNull($code);
        $this->assertSame($gtin, $code->gtin);
        $this->assertSame($expiry, $code->expiresAt?->toDateString());
    }

    public function test_plain_barcodes_and_foreign_codes_are_not_gs1(): void
    {
        $this->assertNull(Gs1Code::parse('4008400402222'));
        $this->assertNull(Gs1Code::parse('https://example.com/gewinnspiel'));
        $this->assertNull(Gs1Code::parse('Hallo Welt'));
        $this->assertNull(Gs1Code::parse('0112'));
    }
}
