<?php

namespace App\Http\Requests;

use App\Enums\ButlerTone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateToneRequest extends FormRequest
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
            'tone' => ['required', Rule::enum(ButlerTone::class)],
        ];
    }
}
