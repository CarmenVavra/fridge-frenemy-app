<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteAccountRequest extends FormRequest
{
    /**
     * Separate error bag, so errors don't show up in the password change form.
     *
     * @var string
     */
    protected $errorBag = 'deleteAccount';

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
            'password' => ['required', 'current_password'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.required' => 'Zum Löschen brauche ich dein Passwort.',
            'password.current_password' => 'Das Passwort stimmt nicht. Dein Konto bleibt.',
        ];
    }
}
