<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $code = strtoupper(trim((string) $this->input('invite_code')));

        $this->merge(['invite_code' => $code === '' ? null : $code]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'household_name' => ['nullable', 'string', 'max:80'],
            'invite_code' => ['nullable', 'string', 'max:12', 'exists:households,invite_code'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'household_name' => 'Haushaltsname',
            'invite_code' => 'Einladungscode',
        ];
    }
}
