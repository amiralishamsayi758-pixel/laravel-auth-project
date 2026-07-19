<?php

namespace App\Support;

final class RegistrationValidation
{
    /**
     * @return array<string, list<string>>
     */
    public static function rules(): array
    {
        return [
            'gmail' => ['required', 'email:rfc', 'lowercase', 'ends_with:@gmail.com', 'max:255', 'unique:users,gmail'],
            'phone' => ['required', 'digits:11', 'regex:/^09[0-9]{9}$/', 'unique:users,phone'],
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[A-Za-z0-9_]+$/', 'unique:users,username'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'gmail.required' => 'وارد کردن آدرس جیمیل الزامی است.',
            'gmail.email' => 'آدرس جیمیل باید معتبر باشد.',
            'gmail.lowercase' => 'آدرس جیمیل باید فقط با حروف کوچک وارد شود.',
            'gmail.ends_with' => 'آدرس جیمیل باید به @gmail.com ختم شود.',
            'gmail.max' => 'آدرس جیمیل نباید بیشتر از ۲۵۵ نویسه باشد.',
            'gmail.unique' => 'این جیمیل قبلاً ثبت شده است.',
            'phone.required' => 'وارد کردن شماره موبایل الزامی است.',
            'phone.digits' => 'شماره موبایل باید دقیقاً ۱۱ رقم باشد.',
            'phone.regex' => 'شماره موبایل باید با 09 شروع شود و ۱۱ رقم داشته باشد.',
            'phone.unique' => 'این شماره موبایل قبلاً ثبت شده است.',
            'username.required' => 'وارد کردن نام کاربری الزامی است.',
            'username.string' => 'نام کاربری باید متن باشد.',
            'username.min' => 'نام کاربری باید حداقل ۳ نویسه باشد.',
            'username.max' => 'نام کاربری نباید بیشتر از ۳۰ نویسه باشد.',
            'username.regex' => 'نام کاربری فقط می‌تواند شامل حروف انگلیسی، عدد و زیرخط باشد.',
            'username.unique' => 'این نام کاربری قبلاً استفاده شده است.',
        ];
    }
}
