<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('registration')) {
            return redirect()->route('register.create');
        }

        return view('auth.verify');
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $request->session()->has('registration')) {
            return redirect()->route('register.create');
        }

        $request->validate([
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => 'وارد کردن کد تأیید الزامی است.',
            'code.digits' => 'کد تأیید باید دقیقاً ۶ رقم باشد.',
        ]);

        $request->session()->put('verification.completed', true);

        return redirect()->route('dashboard');
    }
}
