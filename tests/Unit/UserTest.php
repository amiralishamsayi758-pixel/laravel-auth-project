<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_exposes_only_the_expected_mass_assignable_attributes(): void
    {
        $user = new User;

        $this->assertSame([
            'gmail',
            'phone',
            'username',
            'password',
        ], $user->getFillable());
    }

    public function test_sensitive_authentication_attributes_are_hidden(): void
    {
        $user = new User;

        $this->assertContains('password', $user->getHidden());
        $this->assertContains('remember_token', $user->getHidden());
    }

    public function test_gmail_verified_at_uses_a_datetime_cast(): void
    {
        $user = new User;

        $this->assertSame('datetime', $user->getCasts()['gmail_verified_at']);
    }

    public function test_password_uses_hashed_cast_and_authentication_password_contract(): void
    {
        $user = User::factory()->create(['password' => 'SecurePass123']);

        $this->assertSame('hashed', $user->getCasts()['password']);
        $this->assertTrue(Hash::check('SecurePass123', $user->password));
        $this->assertSame($user->password, $user->getAuthPassword());
        $this->assertSame('password', $user->getAuthPasswordName());
    }

    public function test_password_reset_and_mail_notifications_use_gmail(): void
    {
        $user = User::factory()->make(['gmail' => 'reset-user@gmail.com']);

        $this->assertSame('reset-user@gmail.com', $user->getEmailForPasswordReset());
        $this->assertSame('reset-user@gmail.com', $user->routeNotificationFor('mail'));
    }
}
