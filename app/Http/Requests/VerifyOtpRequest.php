<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'numeric', 'digits:6'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'وارد کردن کد تأیید الزامی است.',
            'code.numeric' => 'کد تأیید باید فقط شامل عدد باشد.',
            'code.digits' => 'کد تأیید باید دقیقاً ۶ رقم باشد.',
        ];
    }
}
