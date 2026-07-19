<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'وارد کردن جیمیل یا نام کاربری الزامی است.',
            'login.max' => 'شناسه ورود نباید بیشتر از ۲۵۵ نویسه باشد.',
            'password.required' => 'وارد کردن رمز عبور الزامی است.',
        ]);

        $login = trim($validated['login']);
        $isGmail = filter_var($login, FILTER_VALIDATE_EMAIL) !== false;
        $identifierColumn = $isGmail ? 'gmail' : 'username';
        $identifierValue = $isGmail ? strtolower($login) : $login;

        if (! Auth::attempt([
            $identifierColumn => $identifierValue,
            'password' => $validated['password'],
        ])) {
            throw ValidationException::withMessages([
                'login' => 'اطلاعات ورود صحیح نیست.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
