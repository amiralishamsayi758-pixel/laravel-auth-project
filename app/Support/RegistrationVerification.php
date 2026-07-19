<?php

namespace App\Support;

use App\Models\RegistrationVerification as RegistrationVerificationModel;
use App\Notifications\RegistrationVerificationCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

final class RegistrationVerification
{
    public function issue(string $gmail): RegistrationVerificationModel
    {
        return $this->storeAndSend($gmail, false);
    }

    public function resend(string $gmail): RegistrationVerificationModel
    {
        return $this->storeAndSend($gmail, true);
    }

    public function find(string $gmail): ?RegistrationVerificationModel
    {
        return RegistrationVerificationModel::query()->where('gmail', $gmail)->first();
    }

    public function isValid(RegistrationVerificationModel $challenge, string $code): bool
    {
        return ! $challenge->expires_at->isPast()
            && Hash::check($code, $challenge->code_hash);
    }

    private function storeAndSend(string $gmail, bool $enforceCooldown): RegistrationVerificationModel
    {
        $expiresAfterSeconds = (int) config('verification.expires_after_seconds', 600);
        $cooldownSeconds = (int) config('verification.resend_cooldown_seconds', 90);

        [$challenge, $code] = DB::transaction(function () use ($cooldownSeconds, $enforceCooldown, $expiresAfterSeconds, $gmail): array {
            $existing = RegistrationVerificationModel::query()
                ->where('gmail', $gmail)
                ->lockForUpdate()
                ->first();

            if ($enforceCooldown && $existing?->resend_available_at?->isFuture()) {
                throw ValidationException::withMessages([
                    'resend' => 'لطفاً تا پایان شمارش معکوس صبر کنید.',
                ]);
            }

            do {
                $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            } while ($existing && Hash::check($code, $existing->code_hash));

            $challenge = RegistrationVerificationModel::query()->updateOrCreate(
                ['gmail' => $gmail],
                [
                    'code' => app()->environment(['local', 'testing']) ? $code : null,
                    'code_hash' => Hash::make($code),
                    'expires_at' => now()->addSeconds($expiresAfterSeconds),
                    'resend_available_at' => now()->addSeconds($cooldownSeconds),
                ],
            );

            return [$challenge, $code];
        });

        Notification::route('mail', $gmail)->notify(new RegistrationVerificationCode(
            $code,
            (int) ceil($expiresAfterSeconds / 60),
        ));

        return $challenge;
    }
}
