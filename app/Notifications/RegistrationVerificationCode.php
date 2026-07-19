<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationVerificationCode extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $code,
        public readonly int $expiresAfterMinutes,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('کد تأیید ثبت‌نام')
            ->greeting('سلام!')
            ->line("کد تأیید شش‌رقمی شما: {$this->code}")
            ->line("این کد تا {$this->expiresAfterMinutes} دقیقه معتبر است.")
            ->line('اگر شما درخواست ثبت‌نام نداده‌اید، این پیام را نادیده بگیرید.');
    }
}
