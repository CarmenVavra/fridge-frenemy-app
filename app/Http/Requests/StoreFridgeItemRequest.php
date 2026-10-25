<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFridgeItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->household_id !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'barcode' => ['nullable', 'string', 'regex:/^[0-9]{6,14}$/'],
            'category' => ['nullable', 'string', 'max:60'],
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'unit' => ['nullable', 'string', 'max:20'],
            'expires_at' => UpdateFridgeItemExpiryRequest::RULE,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'Produkt',
            'quantity' => 'Menge',
            'expires_at' => 'Haltbar bis',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return UpdateFridgeItemExpiryRequest::MESSAGES;
    }
}
