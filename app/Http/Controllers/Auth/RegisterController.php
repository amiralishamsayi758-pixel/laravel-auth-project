<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\RegistrationValidation;
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
        $validated = $request->validate(
            RegistrationValidation::rules(),
            RegistrationValidation::messages(),
        );

        $request->session()->forget(['verification.completed', 'registered_user_id']);
        $request->session()->put('registration', $validated);

        return redirect()->route('verification.create');
    }
}
