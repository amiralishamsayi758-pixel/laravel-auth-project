<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\PasswordValidation;
use App\Support\RegistrationValidation;
use App\Support\RegistrationVerification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request, RegistrationVerification $verification): RedirectResponse
    {
        $validated = $request->validate([
            ...RegistrationValidation::rules(),
            'password' => PasswordValidation::rules(),
        ], [
            ...RegistrationValidation::messages(),
            ...PasswordValidation::messages(),
        ]);

        $passwordHash = Hash::make($validated['password']);
        unset($validated['password']);

        $request->session()->forget('verification.completed');
        $request->session()->put('registration', [
            ...$validated,
            'password_hash' => $passwordHash,
        ]);
        $verification->issue($validated['gmail']);

        return redirect()->route('verification.create');
    }
}
