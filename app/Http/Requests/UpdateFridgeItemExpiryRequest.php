<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFridgeItemExpiryRequest extends FormRequest
{
    /**
     * Best-before date as sent by <input type="date">, shared with StoreFridgeItemRequest.
     *
     * @var list<string>
     */
    public const RULE = ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before:+10 years'];

    /**
     * @var array<string, string>
     */
    public const MESSAGES = [
        'expires_at.after_or_equal' => 'Das Datum liegt in der Vergangenheit. Dann ist es schon abgelaufen.',
        'expires_at.before' => 'So lange hält nicht einmal Dosenravioli.',
    ];

    /**
     * Membership is checked by the "can:update,item" route middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'expires_at' => self::RULE,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'expires_at' => 'Haltbar bis',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return self::MESSAGES;
    }
}
