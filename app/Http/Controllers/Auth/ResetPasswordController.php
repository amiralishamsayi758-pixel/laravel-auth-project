<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PasswordValidation;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    public function create(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'gmail' => $request->query('gmail', ''),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'gmail' => ['required', 'email:rfc', 'lowercase', 'ends_with:@gmail.com', 'max:255'],
            'password' => PasswordValidation::rules(),
        ], PasswordValidation::messages());

        $status = Password::reset(
            [
                'gmail' => $validated['gmail'],
                'password' => $validated['password'],
                'password_confirmation' => $request->string('password_confirmation')->toString(),
                'token' => $validated['token'],
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()
                ->withInput($request->only('gmail'))
                ->withErrors(['gmail' => 'لینک بازیابی نامعتبر یا منقضی شده است.']);
        }

        return redirect()->route('login')->with('status', 'رمز عبور شما با موفقیت تغییر کرد. اکنون می‌توانید وارد شوید.');
    }
}
