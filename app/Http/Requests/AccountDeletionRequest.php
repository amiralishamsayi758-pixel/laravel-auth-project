<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AccountDeletionRequest extends FormRequest
{
    protected $errorBag = 'accountDeletion';

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
}
