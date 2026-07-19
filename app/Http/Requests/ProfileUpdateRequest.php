<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    protected $errorBag = 'profileUpdate';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()->getKey();

        return [
            'gmail' => [
                'required',
                'email:rfc',
                'lowercase',
                'ends_with:@gmail.com',
                'max:255',
                Rule::unique('users', 'gmail')->ignore($userId),
            ],
            'phone' => [
                'required',
                'digits:11',
                'regex:/^09[0-9]{9}$/',
                Rule::unique('users', 'phone')->ignore($userId),
            ],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[A-Za-z0-9_]+$/',
                Rule::unique('users', 'username')->ignore($userId),
            ],
        ];
    }
}
