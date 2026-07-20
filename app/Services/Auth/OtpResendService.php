<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OtpResendService
{
    public function __construct(private OtpCodeService $otpCodeService) {}

    public function resend(?string $attemptId): ?User
    {
        if (! is_string($attemptId) || $attemptId === '') {
            return null;
        }

        $expiresAfterSeconds = (int) config('verification.expires_after_seconds', 600);

        $result = DB::transaction(function () use ($attemptId, $expiresAfterSeconds): ?array {
            $user = User::query()
                ->where('registration_attempt_id', $attemptId)
                ->lockForUpdate()
                ->first();

            if (! $user) {
                return null;
            }

            if ($user->status !== User::STATUS_PENDING || $user->verification_used_at !== null) {
                throw ValidationException::withMessages(['resend' => 'این ثبت‌نام قبلاً تأیید شده است.']);
            }

            if ($user->resend_available_at?->isFuture()) {
                throw ValidationException::withMessages(['resend' => 'لطفاً تا پایان شمارش معکوس صبر کنید.']);
            }

            do {
                $code = $this->otpCodeService->generate();
            } while ($code === $user->verification_code);

            $user->forceFill([
                'verification_code' => $code,
                'verification_attempts' => 0,
                'verification_expires_at' => now()->addSeconds($expiresAfterSeconds),
                'resend_available_at' => now()->addSeconds((int) config('verification.resend_cooldown_seconds', 90)),
                'verification_used_at' => null,
            ])->save();

            return [$user, $code];
        });

        if ($result === null) {
            return null;
        }

        [$user, $code] = $result;
        $this->otpCodeService->send($user, $code, $expiresAfterSeconds);

        return $user;
    }
}
