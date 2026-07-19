<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\RegistrationVerification as RegistrationVerificationModel;
use App\Models\User;
use App\Support\RegistrationValidation;
use App\Support\RegistrationVerification;
use Illuminate\Auth\Events\Registered;
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
    public function create(Request $request, RegistrationVerification $verification): View|RedirectResponse
    {
        $gmail = $this->pendingGmail($request);

        if ($gmail === null) {
            return redirect()->route('register.create')->withErrors([
                'registration' => 'اطلاعات ثبت‌نام موجود نیست. لطفاً دوباره ثبت‌نام کنید.',
            ]);
        }

        $challenge = $verification->find($gmail);

        return view('auth.verify', [
            'resendAvailableAt' => $challenge?->resend_available_at?->timestamp ?? 0,
        ]);
    }

    public function store(Request $request, RegistrationVerification $verification): RedirectResponse
    {
        $gmail = $this->pendingGmail($request);

        if ($gmail === null) {
            return redirect()->route('register.create')->withErrors([
                'registration' => 'اطلاعات ثبت‌نام موجود نیست. لطفاً دوباره ثبت‌نام کنید.',
            ]);
        }

        $validatedCode = $request->validate([
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => 'وارد کردن کد تأیید الزامی است.',
            'code.digits' => 'کد تأیید باید دقیقاً ۶ رقم باشد.',
        ]);

        $registration = $request->session()->get('registration');
        $passwordHash = is_array($registration) ? ($registration['password_hash'] ?? null) : null;

        if (! is_string($passwordHash) || Hash::needsRehash($passwordHash)) {
            $request->session()->forget('registration');

            return redirect()->route('register.create')->withErrors([
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
            $user = DB::transaction(function () use ($gmail, $passwordHash, $validatedCode, $validatedRegistration, $verification): User {
                $challenge = RegistrationVerificationModel::query()
                    ->where('gmail', $gmail)
                    ->lockForUpdate()
                    ->first();

                if (! $challenge || ! $verification->isValid($challenge, $validatedCode['code'])) {
                    throw ValidationException::withMessages([
                        'code' => 'کد تأیید واردشده صحیح نیست یا منقضی شده است.',
                    ]);
                }

                $user = User::create([
                    ...$validatedRegistration,
                    'password' => $passwordHash,
                ]);

                $user->forceFill(['gmail_verified_at' => now()])->save();
                $challenge->delete();

                return $user;
            });
        } catch (QueryException) {
            return redirect()
                ->route('register.create')
                ->withErrors([
                    'registration' => 'اطلاعات واردشده قبلاً ثبت شده است. لطفاً اطلاعات دیگری وارد کنید.',
                ])
                ->withInput($validatedRegistration);
        }

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget(['registration', 'verification.completed']);

        return redirect()->route('dashboard');
    }

    public function resend(Request $request, RegistrationVerification $verification): RedirectResponse
    {
        $gmail = $this->pendingGmail($request);

        if ($gmail === null) {
            return redirect()->route('register.create')->withErrors([
                'registration' => 'اطلاعات ثبت‌نام موجود نیست. لطفاً دوباره ثبت‌نام کنید.',
            ]);
        }

        $verification->resend($gmail);

        return back()->with('status', 'verification-code-resent');
    }

    private function pendingGmail(Request $request): ?string
    {
        $registration = $request->session()->get('registration');
        $gmail = is_array($registration) ? ($registration['gmail'] ?? null) : null;

        return is_string($gmail) && $gmail !== '' ? $gmail : null;
    }
}
