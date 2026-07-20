<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\RegistrationVerificationCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_access_admin_gate_allows_admin_and_denies_normal_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('access-admin'));
        $this->assertTrue($admin->can('access-admin'));
        $this->assertFalse(Gate::forUser($user)->allows('access-admin'));
        $this->assertFalse($user->can('access-admin'));
    }

    public function test_admin_route_enforces_guest_verification_and_gate_layers(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirectToRoute('login');

        $unverifiedAdmin = User::factory()->admin()->unverified()->create();
        $this->actingAs($unverifiedAdmin)->get(route('admin.dashboard'))
            ->assertRedirectToRoute('verification.notice');

        $normalUser = User::factory()->create();
        $this->actingAs($normalUser)->get(route('admin.dashboard'))->assertForbidden();

        $this->withoutVite();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee($admin->username);
    }

    public function test_dashboard_hides_admin_link_from_user_and_shows_it_to_admin(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('href="'.route('admin.dashboard').'"', false);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('admin.dashboard').'"', false);
    }

    public function test_registration_input_cannot_set_role_or_store_it_temporarily(): void
    {
        Notification::fake();
        $payload = [
            'gmail' => 'role-test@gmail.com',
            'phone' => '09123456789',
            'username' => 'role_test',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'role' => UserRole::Admin->value,
        ];

        $this->post(route('register.store'), $payload)
            ->assertSessionMissing('registration.role');
        $this->post(route('verification.store'), ['code' => $this->latestRegistrationCode()]);

        $this->assertSame(UserRole::User, User::query()->sole()->role);
    }

    private function latestRegistrationCode(): string
    {
        $code = null;

        Notification::assertSentOnDemand(
            RegistrationVerificationCode::class,
            function (RegistrationVerificationCode $notification) use (&$code): bool {
                $code = $notification->code;

                return true;
            },
        );

        return (string) $code;
    }

    public function test_profile_avatar_and_password_payloads_cannot_change_role(): void
    {
        $user = User::factory()->create(['password' => 'CurrentPass123']);

        $this->actingAs($user)->patch(route('profile.update'), [
            'gmail' => $user->gmail,
            'phone' => $user->phone,
            'username' => $user->username,
            'role' => UserRole::Admin->value,
        ]);
        $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'CurrentPass123',
            'password' => 'NewSecurePass456',
            'password_confirmation' => 'NewSecurePass456',
            'role' => UserRole::Admin->value,
        ]);
        $this->actingAs($user)->delete(route('profile.avatar.destroy'), [
            'role' => UserRole::Admin->value,
        ]);

        $this->assertSame(UserRole::User, $user->fresh()->role);
    }
}
