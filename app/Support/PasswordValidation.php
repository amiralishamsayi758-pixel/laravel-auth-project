<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

final class PasswordValidation
{
    /**
     * @return list<mixed>
     */
    public static function rules(): array
    {
        return [
            'required',
            'string',
            'confirmed',
            Password::min(8)->letters()->mixedCase()->numbers(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'password.required' => 'وارد کردن رمز عبور الزامی است.',
            'password.confirmed' => 'تکرار رمز عبور با رمز عبور یکسان نیست.',
            'password.min' => 'رمز عبور باید حداقل ۸ نویسه باشد.',
            'password.letters' => 'رمز عبور باید شامل حروف باشد.',
            'password.mixed' => 'رمز عبور باید شامل حروف کوچک و بزرگ انگلیسی باشد.',
            'password.numbers' => 'رمز عبور باید شامل حداقل یک عدد باشد.',
        ];
    }
}
