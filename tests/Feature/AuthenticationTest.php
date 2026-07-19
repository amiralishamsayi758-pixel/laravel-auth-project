<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_loads(): void
    {
        $this->withoutVite();
        $this->get(route('login'))->assertOk()->assertSee('name="login"', false);
    }

    public function test_gmail_login_succeeds(): void
    {
        $user = User::factory()->create(['password' => 'SecurePass123']);

        $this->post(route('login.store'), ['login' => $user->gmail, 'password' => 'SecurePass123'])
            ->assertRedirectToRoute('dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_username_login_succeeds_and_honors_intended_destination(): void
    {
        $user = User::factory()->create(['password' => 'SecurePass123']);

        $this->withSession(['url.intended' => route('dashboard')])
            ->post(route('login.store'), ['login' => $user->username, 'password' => 'SecurePass123'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_incorrect_credentials_return_the_same_generic_error(): void
    {
        User::factory()->create(['gmail' => 'known@gmail.com']);

        foreach (['known@gmail.com', 'missing@gmail.com'] as $login) {
            $this->from(route('login'))->post(route('login.store'), [
                'login' => $login,
                'password' => 'WrongPassword123',
            ])->assertRedirectToRoute('login')->assertSessionHasErrors([
                'login' => 'اطلاعات ورود صحیح نیست.',
            ]);
            $this->assertGuest();
        }
    }

    public function test_authenticated_users_cannot_access_guest_pages(): void
    {
        $user = User::factory()->create();

        foreach (['login', 'register.create', 'verification.create'] as $route) {
            $this->actingAs($user)->get(route($route))->assertRedirectToRoute('dashboard');
        }
    }

    public function test_guest_is_redirected_from_dashboard_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirectToRoute('login');
    }

    public function test_authenticated_dashboard_displays_user_without_legacy_session_id(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee($user->gmail)
            ->assertSee($user->phone)
            ->assertSee($user->username)
            ->assertSee('action="'.route('logout').'"', false);
    }

    public function test_post_logout_logs_out_and_invalidates_authentication(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('logout'))->assertRedirectToRoute('login');
        $this->assertGuest();
    }

    public function test_get_logout_is_unavailable(): void
    {
        $this->get('/logout')->assertMethodNotAllowed();
    }
}
