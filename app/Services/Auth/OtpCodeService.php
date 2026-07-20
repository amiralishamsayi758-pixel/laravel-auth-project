<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Notifications\RegistrationVerificationCode;
use Illuminate\Support\Facades\Notification;

class OtpCodeService
{
    public function generate(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function send(User $user, string $code, int $expiresAfterSeconds): void
    {
        Notification::route('mail', $user->gmail)->notify(new RegistrationVerificationCode(
            $code,
            (int) ceil($expiresAfterSeconds / 60),
        ));
    }
}
