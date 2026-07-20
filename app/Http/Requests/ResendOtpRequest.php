<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResendOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The registration attempt is resolved from the trusted server-side session.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
