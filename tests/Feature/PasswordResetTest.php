<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private const NEW_PASSWORD = 'NewSecurePass456';

    public function test_forgot_password_page_loads_for_guest_and_login_links_to_it(): void
    {
        $this->withoutVite();

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('name="gmail"', false);
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('href="'.route('password.request').'"', false);
    }

    public function test_authenticated_user_is_redirected_from_all_reset_routes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('password.request'))->assertRedirectToRoute('dashboard');
        $this->actingAs($user)->post(route('password.email'), ['gmail' => $user->gmail])->assertRedirectToRoute('dashboard');
        $this->actingAs($user)->get(route('password.reset', ['token' => 'token']))->assertRedirectToRoute('dashboard');
        $this->actingAs($user)->post(route('password.update'))->assertRedirectToRoute('dashboard');
    }

    public function test_reset_link_request_validates_required_gmail_and_format(): void
    {
        $this->post(route('password.email'))->assertSessionHasErrors(['gmail']);
        $this->post(route('password.email'), ['gmail' => 'not-an-email'])->assertSessionHasErrors(['gmail']);
        $this->post(route('password.email'), ['gmail' => 'person@example.com'])->assertSessionHasErrors(['gmail']);
    }

    public function test_existing_user_receives_builtin_notification_with_secure_gmail_url_and_hashed_token(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['gmail' => $user->gmail])
            ->assertSessionHas('status', ForgotPasswordController::STATUS);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user): bool {
            $mail = $notification->toMail($user);
            $storedToken = DB::table('password_reset_tokens')->where('email', $user->gmail)->value('token');

            $this->assertStringContainsString(route('password.reset', ['token' => $notification->token]), $mail->actionUrl);
            $this->assertStringContainsString('gmail='.urlencode($user->gmail), $mail->actionUrl);
            $this->assertNotSame($notification->token, $storedToken);
            $this->assertTrue(Hash::check($notification->token, $storedToken));

            return true;
        });
    }

    public function test_unknown_and_existing_gmail_receive_identical_public_response(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        foreach ([$user->gmail, 'missing@gmail.com'] as $gmail) {
            $this->post(route('password.email'), ['gmail' => $gmail])
                ->assertSessionHas('status', ForgotPasswordController::STATUS)
                ->assertSessionHasNoErrors();
        }

        Notification::assertSentTo($user, ResetPasswordNotification::class);
        Notification::assertCount(1);
    }

    public function test_reset_link_submission_route_is_throttled(): void
    {
        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->post(route('password.email'), ['gmail' => "missing{$attempt}@gmail.com"])->assertRedirect();
        }

        $this->post(route('password.email'), ['gmail' => 'missing7@gmail.com'])->assertTooManyRequests();
    }

    public function test_valid_reset_link_opens_with_hidden_token_and_prefilled_gmail(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'gmail' => $user->gmail]))
            ->assertOk()
            ->assertSee('name="token" value="'.$token.'"', false)
            ->assertSee('value="'.$user->gmail.'"', false);
    }

    public function test_password_confirmation_is_required_and_weak_password_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'gmail' => $user->gmail,
            'password' => self::NEW_PASSWORD,
        ])->assertSessionHasErrors(['password']);

        $this->post(route('password.update'), $this->resetPayload($user, $token, 'password'))
            ->assertSessionHasErrors(['password']);
    }

    public function test_invalid_token_and_cross_user_token_are_rejected(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $token = Password::createToken($firstUser);

        $this->post(route('password.update'), $this->resetPayload($firstUser, 'invalid-token'))
            ->assertSessionHasErrors(['gmail']);
        $this->post(route('password.update'), $this->resetPayload($secondUser, $token))
            ->assertSessionHasErrors(['gmail']);

        $this->assertTrue(Hash::check('Password123', $firstUser->fresh()->password));
        $this->assertTrue(Hash::check('Password123', $secondUser->fresh()->password));
    }

    public function test_expired_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        DB::table('password_reset_tokens')->where('email', $user->gmail)->update([
            'created_at' => now()->subMinutes((int) config('auth.passwords.users.expire') + 1),
        ]);

        $this->post(route('password.update'), $this->resetPayload($user, $token))
            ->assertSessionHasErrors(['gmail']);
        $this->assertTrue(Hash::check('Password123', $user->fresh()->password));
    }

    public function test_valid_token_resets_once_rotates_remember_token_dispatches_event_and_keeps_user_logged_out(): void
    {
        Event::fake([PasswordReset::class]);
        $user = User::factory()->create([
            'password' => 'OldSecurePass123',
            'remember_token' => 'original-remember-token',
        ]);
        $token = Password::createToken($user);

        $this->post(route('password.update'), $this->resetPayload($user, $token))
            ->assertRedirectToRoute('login')
            ->assertSessionHas('status');

        $freshUser = $user->fresh();
        $this->assertGuest();
        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $freshUser->password));
        $this->assertNotSame(self::NEW_PASSWORD, $freshUser->getRawOriginal('password'));
        $this->assertNotSame('original-remember-token', $freshUser->remember_token);
        Event::assertDispatched(PasswordReset::class, fn (PasswordReset $event): bool => $event->user->is($user));

        $this->post(route('password.update'), $this->resetPayload($user, $token, 'AnotherPass789'))
            ->assertSessionHasErrors(['gmail']);

        $this->post(route('login.store'), ['login' => $user->gmail, 'password' => 'OldSecurePass123'])
            ->assertSessionHasErrors(['login']);
        $this->assertGuest();

        $this->post(route('login.store'), ['login' => $user->gmail, 'password' => self::NEW_PASSWORD])
            ->assertRedirectToRoute('dashboard');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * @return array<string, string>
     */
    private function resetPayload(User $user, string $token, string $password = self::NEW_PASSWORD): array
    {
        return [
            'token' => $token,
            'gmail' => $user->gmail,
            'password' => $password,
            'password_confirmation' => $password,
        ];
    }
}
