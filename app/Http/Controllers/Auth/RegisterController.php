<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'gmail' => ['required', 'email:rfc', 'lowercase', 'ends_with:@gmail.com', 'max:255'],
            'phone' => ['required', 'digits:11', 'regex:/^09[0-9]{9}$/'],
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[A-Za-z0-9_]+$/'],
        ], [
            'gmail.required' => 'وارد کردن آدرس جیمیل الزامی است.',
            'gmail.email' => 'آدرس جیمیل باید معتبر باشد.',
            'gmail.lowercase' => 'آدرس جیمیل باید فقط با حروف کوچک وارد شود.',
            'gmail.ends_with' => 'آدرس جیمیل باید به @gmail.com ختم شود.',
            'gmail.max' => 'آدرس جیمیل نباید بیشتر از ۲۵۵ نویسه باشد.',
            'phone.required' => 'وارد کردن شماره موبایل الزامی است.',
            'phone.digits' => 'شماره موبایل باید دقیقاً ۱۱ رقم باشد.',
            'phone.regex' => 'شماره موبایل باید با 09 شروع شود و ۱۱ رقم داشته باشد.',
            'username.required' => 'وارد کردن نام کاربری الزامی است.',
            'username.string' => 'نام کاربری باید متن باشد.',
            'username.min' => 'نام کاربری باید حداقل ۳ نویسه باشد.',
            'username.max' => 'نام کاربری نباید بیشتر از ۳۰ نویسه باشد.',
            'username.regex' => 'نام کاربری فقط می‌تواند شامل حروف انگلیسی، عدد و زیرخط باشد.',
        ]);

        $request->session()->forget('verification.completed');
        $request->session()->put('registration', $validated);

        return redirect()->route('verification.create');
    }
}
