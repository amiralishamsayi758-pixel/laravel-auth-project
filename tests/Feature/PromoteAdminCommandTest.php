<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromoteAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_user_is_promoted_without_other_changes_or_verification(): void
    {
        $user = User::factory()->unverified()->create();
        $profile = $user->only(['gmail', 'phone', 'username', 'password', 'remember_token', 'avatar_path']);

        $this->artisan('user:promote-admin', ['gmail' => $user->gmail])
            ->expectsOutput('The user was promoted to administrator.')
            ->assertSuccessful();

        $freshUser = $user->fresh();
        $this->assertSame(UserRole::Admin, $freshUser->role);
        $this->assertFalse($freshUser->hasVerifiedEmail());
        $this->assertSame($profile, $freshUser->only(array_keys($profile)));
    }

    public function test_already_admin_promotion_is_idempotent(): void
    {
        $admin = User::factory()->admin()->create();

        $this->artisan('user:promote-admin', ['gmail' => $admin->gmail])
            ->expectsOutput('The user is already an administrator.')
            ->assertSuccessful();

        $this->assertSame(UserRole::Admin, $admin->fresh()->role);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_missing_user_fails_without_creating_account(): void
    {
        $this->artisan('user:promote-admin', ['gmail' => 'missing@gmail.com'])
            ->expectsOutput('No user exists with the provided Gmail address.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_command_output_does_not_contain_sensitive_values(): void
    {
        $user = User::factory()->create([
            'password' => 'SensitivePass123',
            'remember_token' => 'sensitive-remember-token',
        ]);
        $passwordHash = $user->getRawOriginal('password');

        $this->artisan('user:promote-admin', ['gmail' => $user->gmail])
            ->doesntExpectOutputToContain($passwordHash)
            ->doesntExpectOutputToContain('sensitive-remember-token')
            ->assertSuccessful();
    }
}
