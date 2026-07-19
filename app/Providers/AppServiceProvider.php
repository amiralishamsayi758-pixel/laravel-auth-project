<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('access-admin', fn (User $user): bool => $user->role === UserRole::Admin);

        ResetPassword::createUrlUsing(fn (object $notifiable, string $token): string => route('password.reset', [
            'token' => $token,
            'gmail' => $notifiable->getEmailForPasswordReset(),
        ]));
    }
}
