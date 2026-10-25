<?php

namespace App\Http\Requests;

use App\Support\Gs1Code;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class ScanBarcodeRequest extends FormRequest
{
    /** Expiry date carried by a GS1 2D code, if the scanned code had one. */
    private ?CarbonImmutable $codeExpiry = null;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * The scanner sends the raw decoded text. A GS1 DataMatrix/QR code is reduced
     * to its product number here, so the usual barcode rule applies to both.
     */
    protected function prepareForValidation(): void
    {
        $gs1 = Gs1Code::parse((string) $this->input('barcode'));

        if ($gs1 !== null) {
            $this->codeExpiry = $gs1->expiresAt;
            $this->merge(['barcode' => $gs1->gtin]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'barcode' => ['required', 'string', 'regex:/^[0-9]{6,14}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'barcode.regex' => 'Das ist kein Produkt-Code. Ich will Lebensmittel, keine Links.',
        ];
    }

    public function codeExpiry(): ?CarbonImmutable
    {
        return $this->codeExpiry;
    }
}
