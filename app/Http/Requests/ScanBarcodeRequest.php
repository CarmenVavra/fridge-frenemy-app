<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScanBarcodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
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
}
