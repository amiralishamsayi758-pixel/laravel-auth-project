<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public const STATUS = 'اگر حسابی با این جیمیل وجود داشته باشد، لینک بازیابی رمز عبور ارسال خواهد شد.';

    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'gmail' => ['required', 'email:rfc', 'lowercase', 'ends_with:@gmail.com', 'max:255'],
        ]);

        Password::sendResetLink(['gmail' => $validated['gmail']]);

        return back()->with('status', self::STATUS);
    }
}
