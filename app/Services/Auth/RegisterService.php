<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegisterService
{
    public function __construct(private OtpCodeService $otpCodeService) {}

    /**
     * @param  array<string, string>  $attributes
     */
    public function register(array $attributes): User
    {
        $code = $this->otpCodeService->generate();
        $expiresAfterSeconds = (int) config('verification.expires_after_seconds', 600);

        $user = DB::transaction(fn (): User => User::create([
            ...$attributes,
            'password' => Hash::make($attributes['password']),
            'status' => User::STATUS_PENDING,
            'registration_attempt_id' => (string) Str::uuid(),
            'verification_code' => $code,
            'verification_attempts' => 0,
            'verification_expires_at' => now()->addSeconds($expiresAfterSeconds),
            'resend_available_at' => now()->addSeconds((int) config('verification.resend_cooldown_seconds', 90)),
            'verification_used_at' => null,
        ]));

        $this->otpCodeService->send($user, $code, $expiresAfterSeconds);

        return $user;
    }
}
