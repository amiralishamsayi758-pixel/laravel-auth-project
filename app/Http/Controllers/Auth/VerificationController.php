<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\RegistrationValidation;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

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

        $validatedCode = $request->validate([
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => 'وارد کردن کد تأیید الزامی است.',
            'code.digits' => 'کد تأیید باید دقیقاً ۶ رقم باشد.',
        ]);

        $developmentCode = (string) config('verification.development_code');

        if (! app()->environment(['local', 'testing']) || ! hash_equals($developmentCode, $validatedCode['code'])) {
            throw ValidationException::withMessages([
                'code' => 'کد تأیید واردشده صحیح نیست.',
            ]);
        }

        $registration = $request->session()->get('registration');
        $passwordHash = is_array($registration) ? ($registration['password_hash'] ?? null) : null;

        if (! is_string($passwordHash) || Hash::needsRehash($passwordHash)) {
            $request->session()->forget('registration');

            return redirect()
                ->route('register.create')
                ->withErrors([
                    'registration' => 'اطلاعات ثبت‌نام کامل نیست. لطفاً دوباره ثبت‌نام کنید.',
                ]);
        }

        $registrationValidator = Validator::make(
            is_array($registration) ? $registration : [],
            RegistrationValidation::rules(),
            RegistrationValidation::messages(),
        );

        if ($registrationValidator->fails()) {
            return redirect()
                ->route('register.create')
                ->withErrors($registrationValidator)
                ->withInput(is_array($registration) ? $registration : []);
        }

        $validatedRegistration = $registrationValidator->validated();

        try {
            $user = DB::transaction(function () use ($passwordHash, $validatedRegistration): User {
                return User::create([
                    ...$validatedRegistration,
                    'gmail_verified_at' => now(),
                    'password' => $passwordHash,
                ]);
            });
        } catch (QueryException) {
            return redirect()
                ->route('register.create')
                ->withErrors([
                    'registration' => 'اطلاعات واردشده قبلاً ثبت شده است. لطفاً اطلاعات دیگری وارد کنید.',
                ])
                ->withInput($validatedRegistration);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget(['registration', 'verification.completed']);

        return redirect()->route('dashboard');
    }
}
