<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VerificationService
{
    public function findPendingUser(?string $attemptId): ?User
    {
        if (! is_string($attemptId) || $attemptId === '') {
            return null;
        }

        return User::query()
            ->where('registration_attempt_id', $attemptId)
            ->where('status', User::STATUS_PENDING)
            ->first();
    }

    public function verify(string $attemptId, string $code): User
    {
        $user = DB::transaction(function () use ($attemptId, $code): ?User {
            $user = User::query()
                ->where('registration_attempt_id', $attemptId)
                ->lockForUpdate()
                ->first();

            if (! $user || ! $this->isValid($user, $code)) {
                $this->recordFailedAttempt($user);

                return null;
            }

            $user->forceFill([
                'status' => User::STATUS_VERIFIED,
                'gmail_verified_at' => now(),
                'verification_used_at' => now(),
                'verification_code' => null,
                'verification_attempts' => 0,
                'verification_expires_at' => null,
                'resend_available_at' => null,
            ])->save();

            return $user;
        });

        if (! $user) {
            throw ValidationException::withMessages([
                'code' => 'کد تأیید نامعتبر، منقضی یا بیش از حد مجاز استفاده شده است.',
            ]);
        }

        event(new Registered($user));
        Auth::login($user);

        return $user;
    }

    private function isValid(User $user, string $code): bool
    {
        return $user->status === User::STATUS_PENDING
            && $user->verification_used_at === null
            && is_string($user->verification_code)
            && $user->verification_attempts < (int) config('verification.max_attempts', 5)
            && $user->verification_expires_at?->isFuture()
            && $code === $user->verification_code;
    }

    private function recordFailedAttempt(?User $user): void
    {
        if ($user
            && $user->status === User::STATUS_PENDING
            && $user->verification_used_at === null
            && $user->verification_expires_at?->isFuture()
            && $user->verification_attempts < (int) config('verification.max_attempts', 5)) {
            $user->increment('verification_attempts');
        }
    }
}
