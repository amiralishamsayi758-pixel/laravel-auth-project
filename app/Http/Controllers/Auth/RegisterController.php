<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\RegistrationValidation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            ...RegistrationValidation::rules(),
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->letters()->mixedCase()->numbers(),
            ],
        ], [
            ...RegistrationValidation::messages(),
            'password.required' => 'وارد کردن رمز عبور الزامی است.',
            'password.confirmed' => 'تکرار رمز عبور با رمز عبور یکسان نیست.',
            'password.min' => 'رمز عبور باید حداقل ۸ نویسه باشد.',
            'password.letters' => 'رمز عبور باید شامل حروف باشد.',
            'password.mixed' => 'رمز عبور باید شامل حروف کوچک و بزرگ انگلیسی باشد.',
            'password.numbers' => 'رمز عبور باید شامل حداقل یک عدد باشد.',
        ]);

        $passwordHash = Hash::make($validated['password']);
        unset($validated['password']);

        $request->session()->forget('verification.completed');
        $request->session()->put('registration', [
            ...$validated,
            'password_hash' => $passwordHash,
        ]);

        return redirect()->route('verification.create');
    }
}
