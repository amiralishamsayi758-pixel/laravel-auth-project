<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'login' => is_string($this->login) ? trim($this->login) : $this->login,
        ]);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'login.required' => 'وارد کردن جیمیل یا نام کاربری الزامی است.',
            'login.max' => 'شناسه ورود نباید بیشتر از ۲۵۵ نویسه باشد.',
            'password.required' => 'وارد کردن رمز عبور الزامی است.',
        ];
    }
}
