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
            'gmail' => ['required', 'email', 'ends_with:@gmail.com'],
            'phone' => ['required', 'digits:11', 'starts_with:09'],
            'username' => ['required', 'string', 'min:3', 'max:50'],
        ]);

        return redirect()
            ->route('verification.create')
            ->with('registration_preview', $validated);
    }
}
