<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\RegistrationVerificationCode;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notice_is_available_to_unverified_user_and_guest_is_redirected(): void
    {
        $this->withoutVite();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('verification.notice'))
            ->assertOk()
            ->assertSee($user->gmail);

        $this->post(route('logout'));
        $this->get(route('verification.notice'))->assertRedirectToRoute('login');
    }

    public function test_verified_user_is_redirected_from_notice_to_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('verification.notice'))
            ->assertRedirectToRoute('dashboard');
    }

    public function test_valid_signed_url_verifies_user_and_dispatches_verified_event(): void
    {
        Event::fake([Verified::class]);
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get($this->verificationUrl($user))
            ->assertRedirectToRoute('dashboard')
            ->assertSessionHas('status', 'email-verified');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertNotNull($user->fresh()->gmail_verified_at);
        Event::assertDispatched(Verified::class, fn (Verified $event): bool => $event->user->is($user));
    }

    public function test_invalid_hash_does_not_verify_user(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->getKey(),
            'hash' => sha1('wrong@gmail.com'),
        ]);

        $this->actingAs($user)->get($url)->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_invalid_signature_does_not_verify_user(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get($this->verificationUrl($user).'&tampered=1')->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_guest_cannot_verify_email(): void
    {
        $user = User::factory()->unverified()->create();

        $this->get($this->verificationUrl($user))->assertRedirectToRoute('login');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_user_cannot_verify_another_users_email(): void
    {
        $authenticatedUser = User::factory()->unverified()->create();
        $otherUser = User::factory()->unverified()->create();

        $this->actingAs($authenticatedUser)->get($this->verificationUrl($otherUser))->assertForbidden();
        $this->assertFalse($authenticatedUser->fresh()->hasVerifiedEmail());
        $this->assertFalse($otherUser->fresh()->hasVerifiedEmail());
    }

    public function test_already_verified_user_is_handled_idempotently(): void
    {
        Event::fake([Verified::class]);
        $user = User::factory()->create();
        $verifiedAt = $user->gmail_verified_at;

        $this->actingAs($user)->get($this->verificationUrl($user))->assertRedirectToRoute('dashboard');

        $this->assertTrue($user->fresh()->gmail_verified_at->equalTo($verifiedAt));
        Event::assertNotDispatched(Verified::class);
    }

    public function test_unverified_user_can_resend_notification(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->post(route('verification.send'))
            ->assertRedirect()
            ->assertSessionHas('status', 'verification-link-sent');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_verified_user_does_not_receive_resend_notification(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('verification.send'))
            ->assertRedirectToRoute('dashboard');
        Notification::assertNothingSent();
    }

    public function test_guest_cannot_request_verification_notification(): void
    {
        Notification::fake();

        $this->post(route('verification.send'))->assertRedirectToRoute('login');
        Notification::assertNothingSent();
    }

    public function test_resend_endpoint_is_rate_limited(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->actingAs($user)->post(route('verification.send'))->assertRedirect();
        }

        $this->actingAs($user)->post(route('verification.send'))->assertTooManyRequests();
    }

    public function test_verified_middleware_protects_dashboard(): void
    {
        $unverifiedUser = User::factory()->unverified()->create();
        $verifiedUser = User::factory()->create();

        $this->actingAs($unverifiedUser)->get(route('dashboard'))
            ->assertRedirectToRoute('verification.notice');

        $this->withoutVite();
        $this->actingAs($verifiedUser)->get(route('dashboard'))->assertOk();
    }

    public function test_registration_dispatches_registered_event(): void
    {
        Event::fake([Registered::class]);
        Notification::fake();

        $this->post(route('register.store'), $this->registrationPayload());
        $this->post(route('verification.store'), ['code' => $this->latestRegistrationCode()])
            ->assertRedirectToRoute('dashboard');

        $user = User::query()->sole();
        Event::assertDispatched(Registered::class, fn (Registered $event): bool => $event->user->is($user));
    }

    public function test_registration_sends_one_code_notification_and_needs_no_second_link(): void
    {
        Notification::fake();

        $this->post(route('register.store'), $this->registrationPayload());
        Notification::assertSentOnDemand(RegistrationVerificationCode::class);
        $this->post(route('verification.store'), ['code' => $this->latestRegistrationCode()]);

        $user = User::query()->sole();
        $this->assertTrue($user->hasVerifiedEmail());
        Notification::assertNotSentTo($user, VerifyEmail::class);
    }

    private function verificationUrl(User $user): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ]);
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

    /**
     * @return array<string, string>
     */
    private function registrationPayload(): array
    {
        return [
            'gmail' => 'phase8@gmail.com',
            'phone' => '09123456789',
            'username' => 'phase8_user',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
        ];
    }
}
