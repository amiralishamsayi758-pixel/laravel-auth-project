<?php

namespace App\Http\Requests;

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
        $this->merge([
            'gmail' => is_string($this->gmail) ? strtolower(trim($this->gmail)) : $this->gmail,
            'phone' => is_string($this->phone) ? trim($this->phone) : $this->phone,
            'username' => is_string($this->username) ? trim($this->username) : $this->username,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[A-Za-z0-9_]+$/', 'unique:users,username'],
            'gmail' => ['required', 'email:rfc', 'lowercase', 'ends_with:@gmail.com', 'max:255', 'unique:users,gmail'],
            'phone' => ['required', 'digits:11', 'regex:/^09[0-9]{9}$/', 'unique:users,phone'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.required' => 'وارد کردن نام کاربری الزامی است.',
            'username.string' => 'نام کاربری باید متن باشد.',
            'username.min' => 'نام کاربری باید حداقل ۳ نویسه باشد.',
            'username.max' => 'نام کاربری نباید بیشتر از ۳۰ نویسه باشد.',
            'username.regex' => 'نام کاربری فقط می‌تواند شامل حروف انگلیسی، عدد و زیرخط باشد.',
            'username.unique' => 'این نام کاربری قبلاً استفاده شده است.',
            'gmail.required' => 'وارد کردن آدرس جیمیل الزامی است.',
            'gmail.email' => 'آدرس جیمیل باید معتبر باشد.',
            'gmail.lowercase' => 'آدرس جیمیل باید با حروف کوچک وارد شود.',
            'gmail.ends_with' => 'آدرس جیمیل باید به @gmail.com ختم شود.',
            'gmail.max' => 'آدرس جیمیل نباید بیشتر از ۲۵۵ نویسه باشد.',
            'gmail.unique' => 'این جیمیل قبلاً ثبت شده است.',
            'phone.required' => 'وارد کردن شماره موبایل الزامی است.',
            'phone.digits' => 'شماره موبایل باید دقیقاً ۱۱ رقم باشد.',
            'phone.regex' => 'شماره موبایل باید با 09 شروع شود و ۱۱ رقم داشته باشد.',
            'phone.unique' => 'این شماره موبایل قبلاً ثبت شده است.',
            'password.required' => 'وارد کردن رمز عبور الزامی است.',
            'password.confirmed' => 'تکرار رمز عبور با رمز عبور یکسان نیست.',
            'password.min' => 'رمز عبور باید حداقل ۸ نویسه باشد.',
            'password.letters' => 'رمز عبور باید شامل حروف باشد.',
            'password.mixed' => 'رمز عبور باید شامل حروف کوچک و بزرگ انگلیسی باشد.',
            'password.numbers' => 'رمز عبور باید شامل حداقل یک عدد باشد.',
        ];
    }
}
