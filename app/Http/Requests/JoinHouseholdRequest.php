<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JoinHouseholdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['invite_code' => strtoupper(trim((string) $this->input('invite_code')))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'invite_code' => [
                'required',
                'string',
                'max:12',
                Rule::exists('households', 'invite_code'),
                Rule::notIn([(string) $this->user()?->household?->invite_code]),
            ],
            'confirm_leave' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'invite_code.exists' => 'Diesen Code kennt kein Kühlschrank.',
            'invite_code.not_in' => 'Das ist dein eigener Haushalt. Da bist du schon.',
            'confirm_leave.accepted' => 'Bitte bestätige, dass du deinen bisherigen Haushalt verlässt.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'invite_code' => 'Einladungscode',
            'confirm_leave' => 'Bestätigung',
        ];
    }
}
