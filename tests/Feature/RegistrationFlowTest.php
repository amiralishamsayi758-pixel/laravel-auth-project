<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\RegistrationVerificationCode;
use App\Services\Auth\RegisterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'SecurePass123';

    private const REGISTRATION = [
        'gmail' => 'learner@gmail.com',
        'phone' => '09123456789',
        'username' => 'learner_1',
        'password' => self::PASSWORD,
        'password_confirmation' => self::PASSWORD,
    ];

    public function test_fresh_database_contains_the_complete_users_schema_only(): void
    {
        $columns = Schema::getColumnListing('users');

        foreach ([
            'id',
            'username',
            'gmail',
            'phone',
            'password',
            'gmail_verified_at',
            'avatar_path',
            'role',
            'status',
            'registration_attempt_id',
            'verification_code',
            'verification_attempts',
            'verification_expires_at',
            'resend_available_at',
            'verification_used_at',
        ] as $column) {
            $this->assertContains($column, $columns);
        }

        $this->assertFalse(Schema::hasTable('registration_verifications'));
        $this->assertFalse(Schema::hasTable('legacy_registration_verifications'));
    }

    public function test_registration_page_loads_and_validates_password(): void
    {
        $this->withoutVite();
        $this->get(route('register.create'))->assertOk()->assertSee('name="password"', false);

        $this->post(route('register.store'), array_diff_key(self::REGISTRATION, ['password' => true]))
            ->assertSessionHasErrors(['password']);
    }

    public function test_invalid_registration_data_returns_field_specific_errors(): void
    {
        $this->post(route('register.store'), [
            'gmail' => 'person@example.com',
            'phone' => '08123456789',
            'username' => 'ab',
            'password' => 'weak',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['gmail', 'phone', 'username', 'password']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_normalizes_gmail_before_creating_user(): void
    {
        Notification::fake();

        $payload = self::REGISTRATION;
        $payload['gmail'] = '  Learner@GMAIL.COM  ';

        $this->post(route('register.store'), $payload)->assertSessionHasNoErrors();

        $this->assertSame('learner@gmail.com', User::query()->sole()->gmail);
    }

    public function test_registration_creates_pending_user_with_plain_database_otp_and_no_otp_session_state(): void
    {
        Notification::fake();

        $this->post(route('register.store'), self::REGISTRATION)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('registration_attempt_id')
            ->assertSessionMissing('registration')
            ->assertSessionMissing('verification.code')
            ->assertSessionMissing('verification.code_hash')
            ->assertRedirectToRoute('verification.create');

        $user = User::query()->sole();
        $code = $this->latestCode();

        $this->assertSame(User::STATUS_PENDING, $user->status);
        $this->assertNull($user->gmail_verified_at);
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $code);
        $this->assertSame($code, $user->verification_code);
        $this->assertTrue($user->verification_expires_at->isFuture());
        $this->assertTrue($user->resend_available_at->isFuture());
        $this->assertSame(session('registration_attempt_id'), $user->registration_attempt_id);
        $this->assertTrue(Hash::check(self::PASSWORD, $user->password));
    }

    public function test_correct_database_otp_verifies_the_existing_user(): void
    {
        Notification::fake();
        $this->post(route('register.store'), self::REGISTRATION);
        $userId = User::query()->sole()->id;

        $this->post(route('verification.store'), ['code' => $this->latestCode()])
            ->assertSessionMissing('registration_attempt_id')
            ->assertRedirectToRoute('dashboard');

        $user = User::query()->findOrFail($userId);
        $this->assertDatabaseCount('users', 1);
        $this->assertSame(User::STATUS_VERIFIED, $user->status);
        $this->assertNotNull($user->gmail_verified_at);
        $this->assertNotNull($user->verification_used_at);
        $this->assertNull($user->verification_code);
        $this->assertNull($user->verification_expires_at);
        $this->assertNull($user->resend_available_at);
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_otp_increments_attempts_and_maximum_attempts_blocks_correct_code(): void
    {
        Notification::fake();
        $this->post(route('register.store'), self::REGISTRATION);
        $correctCode = $this->latestCode();
        $incorrectCode = $correctCode === '000000' ? '111111' : '000000';

        for ($attempt = 1; $attempt <= (int) config('verification.max_attempts'); $attempt++) {
            $this->post(route('verification.store'), ['code' => $incorrectCode])
                ->assertSessionHasErrors(['code']);

            $this->assertSame($attempt, User::query()->sole()->verification_attempts);
        }

        $this->post(route('verification.store'), ['code' => $correctCode])
            ->assertSessionHasErrors(['code']);

        $this->assertSame(User::STATUS_PENDING, User::query()->sole()->status);
    }

    public function test_invalid_otp_format_is_rejected_before_verification_logic(): void
    {
        Notification::fake();
        $this->post(route('register.store'), self::REGISTRATION);

        foreach (['12345', 'abcdef'] as $invalidCode) {
            $this->post(route('verification.store'), ['code' => $invalidCode])
                ->assertSessionHasErrors(['code']);
        }

        $user = User::query()->sole();
        $this->assertSame(0, $user->verification_attempts);
        $this->assertSame(User::STATUS_PENDING, $user->status);
    }

    public function test_expired_and_used_otps_are_rejected(): void
    {
        Notification::fake();
        $this->post(route('register.store'), self::REGISTRATION);
        $code = $this->latestCode();

        User::query()->sole()->forceFill(['verification_expires_at' => now()->subSecond()])->save();
        $this->post(route('verification.store'), ['code' => $code])->assertSessionHasErrors(['code']);

        User::query()->sole()->forceFill([
            'verification_expires_at' => now()->addMinute(),
            'verification_used_at' => now(),
        ])->save();
        $this->post(route('verification.store'), ['code' => $code])->assertSessionHasErrors(['code']);

        $this->assertSame(User::STATUS_PENDING, User::query()->sole()->status);
    }

    public function test_resend_updates_same_user_and_invalidates_old_otp(): void
    {
        Notification::fake();
        $this->post(route('register.store'), self::REGISTRATION);
        $user = User::query()->sole();
        $oldCode = $this->latestCode();
        $oldExpiration = $user->verification_expires_at;

        $this->travel(91)->seconds();
        $this->post(route('verification.resend'))->assertRedirect();

        $updated = User::query()->sole();
        $newCode = $this->latestCode();

        $this->assertSame($user->id, $updated->id);
        $this->assertDatabaseCount('users', 1);
        $this->assertNotSame($oldCode, $newCode);
        $this->assertSame($newCode, $updated->verification_code);
        $this->assertTrue($updated->verification_expires_at->isAfter($oldExpiration));
        $this->assertSame(0, $updated->verification_attempts);

        $this->post(route('verification.store'), ['code' => $oldCode])->assertSessionHasErrors(['code']);
        $this->post(route('verification.store'), ['code' => $newCode])->assertRedirectToRoute('dashboard');
    }

    public function test_resend_cooldown_and_route_throttle_are_enforced(): void
    {
        Notification::fake();
        $this->post(route('register.store'), self::REGISTRATION);

        $this->post(route('verification.resend'))->assertSessionHasErrors(['resend']);

        for ($attempt = 2; $attempt <= 3; $attempt++) {
            $this->post(route('verification.resend'))->assertRedirect();
        }

        $this->post(route('verification.resend'))->assertTooManyRequests();
    }

    public function test_multiple_pending_users_have_isolated_otp_state(): void
    {
        Notification::fake();
        $registration = app(RegisterService::class);

        $first = $registration->register($this->userAttributes('first'));
        $firstCode = $this->latestCode();
        $second = $registration->register($this->userAttributes('second'));
        $secondCode = $this->latestCode();

        $this->assertDatabaseCount('users', 2);
        $this->assertNotSame($first->registration_attempt_id, $second->registration_attempt_id);
        $this->assertSame($firstCode, $first->verification_code);
        $this->assertSame($secondCode, $second->verification_code);
    }

    public function test_verification_without_registration_attempt_redirects_safely(): void
    {
        $this->get(route('verification.create'))->assertRedirectToRoute('register.create');
        $this->post(route('verification.store'), ['code' => '123456'])->assertRedirectToRoute('register.create');
        $this->post(route('verification.resend'))->assertRedirectToRoute('register.create');
    }

    public function test_pending_user_cannot_access_dashboard(): void
    {
        Notification::fake();
        $this->post(route('register.store'), self::REGISTRATION);
        $pendingUser = User::query()->sole();

        $this->actingAs($pendingUser)
            ->get(route('dashboard'))
            ->assertRedirectToRoute('verification.notice');
    }

    /** @return array<string, string> */
    private function userAttributes(string $suffix): array
    {
        return [
            'gmail' => "{$suffix}@gmail.com",
            'phone' => $suffix === 'first' ? '09111111111' : '09222222222',
            'username' => "user_{$suffix}",
            'password' => self::PASSWORD,
        ];
    }

    private function latestCode(): string
    {
        $code = null;

        Notification::assertSentOnDemand(
            RegistrationVerificationCode::class,
            function (RegistrationVerificationCode $notification) use (&$code): bool {
                $code = $notification->code;

                return true;
            },
        );

        $this->assertIsString($code);

        return $code;
    }
}
