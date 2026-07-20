<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginService
{
    public function login(string $login, string $password): User
    {
        $isGmail = filter_var($login, FILTER_VALIDATE_EMAIL) !== false;
        $identifierColumn = $isGmail ? 'gmail' : 'username';
        $identifierValue = $isGmail ? strtolower($login) : $login;

        $user = User::query()->where($identifierColumn, $identifierValue)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => 'اطلاعات ورود صحیح نیست.',
            ]);
        }

        Auth::login($user);

        return $user;
    }

    public function logout(): void
    {
        Auth::logout();
    }
}
