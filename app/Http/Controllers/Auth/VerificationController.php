<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResendOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Services\Auth\OtpResendService;
use App\Services\Auth\VerificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function __construct(
        private VerificationService $verificationService,
        private OtpResendService $otpResendService,
    ) {}

    public function create(Request $request): View|RedirectResponse
    {
        $user = $this->verificationService->findPendingUser(
            $request->session()->get('registration_attempt_id'),
        );

        if (! $user) {
            return redirect()->route('register.create')->withErrors([
                'registration' => 'درخواست ثبت‌نام فعالی وجود ندارد.',
            ]);
        }

        return view('auth.verify', [
            'resendAvailableAt' => $user->resend_available_at?->timestamp ?? 0,
        ]);
    }

    public function store(VerifyOtpRequest $request): RedirectResponse
    {
        $attemptId = $request->session()->get('registration_attempt_id');
        $pendingUser = $this->verificationService->findPendingUser($attemptId);

        if (! $pendingUser) {
            return redirect()->route('register.create')->withErrors([
                'registration' => 'درخواست ثبت‌نام فعالی وجود ندارد.',
            ]);
        }

        $this->verificationService->verify($pendingUser->registration_attempt_id, $request->validated('code'));
        $request->session()->regenerate();
        $request->session()->forget('registration_attempt_id');

        return redirect()->route('dashboard');
    }

    public function resend(ResendOtpRequest $request): RedirectResponse
    {
        $user = $this->otpResendService->resend(
            $request->session()->get('registration_attempt_id'),
        );

        if (! $user) {
            return redirect()->route('register.create')->withErrors([
                'registration' => 'درخواست ثبت‌نام فعالی وجود ندارد.',
            ]);
        }

        return back()->with('status', 'verification-code-resent');
    }
}
