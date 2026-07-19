<?php

namespace Tests\Feature;

use App\Models\RegistrationVerification;
use App\Models\User;
use App\Notifications\RegistrationVerificationCode;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_registration_page_loads_with_password_fields(): void
    {
        $this->withoutVite();

        $this->get(route('register.create'))
            ->assertOk()
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false);
    }

    public function test_password_is_required(): void
    {
        $this->post(route('register.store'), array_diff_key(self::REGISTRATION, array_flip(['password', 'password_confirmation'])))
            ->assertSessionHasErrors(['password']);
    }

    public function test_password_confirmation_must_match(): void
    {
        $this->post(route('register.store'), [...self::REGISTRATION, 'password_confirmation' => 'DifferentPass123'])
            ->assertSessionHasErrors(['password']);
    }

    public function test_weak_password_is_rejected(): void
    {
        $this->post(route('register.store'), [
            ...self::REGISTRATION,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors(['password']);
    }

    public function test_valid_registration_stores_only_a_temporary_password_hash(): void
    {
        Notification::fake();
        $response = $this->post(route('register.store'), self::REGISTRATION);

        $response->assertSessionHasNoErrors()
            ->assertSessionHas('registration.gmail', self::REGISTRATION['gmail'])
            ->assertSessionHas('registration.password_hash')
            ->assertSessionMissing('verification.code_hash')
            ->assertSessionMissing('verification.code_expires_at')
            ->assertSessionMissing('verification.resend_available_at')
            ->assertSessionMissing('registration.password')
            ->assertSessionMissing('registration.password_confirmation')
            ->assertRedirectToRoute('verification.create');

        $hash = session('registration.password_hash');
        $this->assertIsString($hash);
        $this->assertNotSame(self::PASSWORD, $hash);
        $this->assertTrue(Hash::check(self::PASSWORD, $hash));
        $challenge = RegistrationVerification::query()->sole();
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $challenge->code);
        $this->assertTrue(Hash::check($challenge->code, $challenge->code_hash));
        $this->assertSame(self::REGISTRATION['gmail'], $challenge->gmail);
        $this->assertNotContains('password', Schema::getColumnListing('registration_verifications'));
        $this->assertNotContains('password_hash', Schema::getColumnListing('registration_verifications'));
        $this->assertDatabaseCount('users', 0);
        Notification::assertSentOnDemand(RegistrationVerificationCode::class);
    }

    public function test_verification_requires_complete_temporary_registration_data(): void
    {
        $this->post(route('register.store'), self::REGISTRATION);
        $code = $this->currentCode();
        session()->forget('registration.gmail');

        $this->post(route('verification.store'), ['code' => $code])
            ->assertRedirectToRoute('register.create')
            ->assertSessionHasErrors(['registration']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_invalid_verification_code_is_rejected(): void
    {
        $this->post(route('register.store'), self::REGISTRATION);

        $this->from(route('verification.create'))
            ->post(route('verification.store'), ['code' => '654321'])
            ->assertRedirectToRoute('verification.create')
            ->assertSessionHasErrors(['code']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_verification_creates_verified_user_with_same_hash_logs_in_and_clears_temporary_data(): void
    {
        $this->post(route('register.store'), self::REGISTRATION);
        $temporaryHash = session('registration.password_hash');
        $code = $this->currentCode();

        $this->post(route('verification.store'), ['code' => $code])
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('registration')
            ->assertSessionMissing('verification.completed')
            ->assertRedirectToRoute('dashboard');

        $user = User::query()->sole();
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->gmail_verified_at);
        $this->assertSame($temporaryHash, $user->getRawOriginal('password'));
        $this->assertTrue(Hash::check(self::PASSWORD, $user->password));
        $this->assertDatabaseCount('registration_verifications', 0);
    }

    public function test_verification_without_registration_redirects_to_register(): void
    {
        $this->get(route('verification.create'))->assertRedirectToRoute('register.create');
        $this->post(route('verification.store'), ['code' => '123456'])->assertRedirectToRoute('register.create');
    }

    public function test_resend_requires_registration_and_restarts_server_cooldown(): void
    {
        $this->post(route('verification.resend'))
            ->assertRedirectToRoute('register.create');

        $this->post(route('register.store'), self::REGISTRATION);
        $initialChallenge = RegistrationVerification::query()->sole();
        $initialExpiration = $initialChallenge->expires_at;
        $initialAvailableAt = $initialChallenge->resend_available_at;
        $initialHash = $initialChallenge->code_hash;
        $oldCode = $initialChallenge->code;

        $this->post(route('verification.resend'))
            ->assertSessionHasErrors(['resend']);

        $this->travel(91)->seconds();

        $this->post(route('verification.resend'))
            ->assertRedirect()
            ->assertSessionHas('status', 'verification-code-resent');

        $replacement = RegistrationVerification::query()->sole();
        $this->assertNotSame($oldCode, $replacement->code);
        $this->assertNotSame($initialHash, $replacement->code_hash);
        $this->assertTrue($replacement->expires_at->isAfter($initialExpiration));
        $this->assertTrue($replacement->resend_available_at->isAfter($initialAvailableAt));
        $this->assertDatabaseCount('registration_verifications', 1);

        $this->post(route('verification.store'), ['code' => $oldCode])
            ->assertSessionHasErrors(['code']);

        $this->post(route('verification.store'), ['code' => $replacement->code])
            ->assertRedirectToRoute('dashboard');
    }

    public function test_expired_verification_code_is_rejected(): void
    {
        $this->post(route('register.store'), self::REGISTRATION);
        $code = $this->currentCode();
        $this->travel(601)->seconds();

        $this->post(route('verification.store'), ['code' => $code])
            ->assertSessionHasErrors(['code']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_verification_page_contains_code_and_countdown_hooks(): void
    {
        $this->withoutVite();
        $this->post(route('register.store'), self::REGISTRATION);

        $this->get(route('verification.create'))
            ->assertOk()
            ->assertSee('data-code-inputs', false)
            ->assertSee('data-resend-timer', false)
            ->assertSee('data-duration="90"', false)
            ->assertSee('data-storage-key="verification_resend_available_at"', false)
            ->assertSee('aria-live="polite"', false);
    }

    public function test_resend_endpoint_is_rate_limited(): void
    {
        $this->post(route('register.store'), self::REGISTRATION);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->post(route('verification.resend'))->assertRedirect();
        }

        $this->post(route('verification.resend'))->assertTooManyRequests();
    }

    public function test_duplicate_registration_values_are_rejected(): void
    {
        $user = User::factory()->create();

        foreach (['gmail', 'phone', 'username'] as $field) {
            $this->post(route('register.store'), [...self::REGISTRATION, $field => $user->{$field}])
                ->assertSessionHasErrors([$field]);
        }

        $this->assertDatabaseCount('users', 1);
    }

    public function test_repeated_verification_does_not_create_a_duplicate(): void
    {
        $this->post(route('register.store'), self::REGISTRATION);
        $code = $this->currentCode();
        $this->post(route('verification.store'), ['code' => $code])->assertRedirectToRoute('dashboard');
        $this->post(route('logout'));
        $this->post(route('verification.store'), ['code' => '123456'])->assertRedirectToRoute('register.create');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_repeated_registration_for_same_gmail_updates_one_challenge(): void
    {
        $this->post(route('register.store'), self::REGISTRATION);
        $firstCode = $this->currentCode();

        $this->post(route('register.store'), self::REGISTRATION);

        $this->assertDatabaseCount('registration_verifications', 1);
        $this->assertNotSame($firstCode, $this->currentCode());
    }

    public function test_production_does_not_store_plain_code(): void
    {
        Notification::fake();
        $this->app->detectEnvironment(fn (): string => 'production');

        app(\App\Support\RegistrationVerification::class)->issue(self::REGISTRATION['gmail']);

        $challenge = RegistrationVerification::query()->sole();
        $this->assertNull($challenge->code);
        $this->assertNotEmpty($challenge->code_hash);
    }

    public function test_unique_gmail_constraint_prevents_duplicate_challenges(): void
    {
        RegistrationVerification::query()->create([
            'gmail' => self::REGISTRATION['gmail'],
            'code' => '123456',
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinute(),
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        RegistrationVerification::query()->create([
            'gmail' => self::REGISTRATION['gmail'],
            'code' => '654321',
            'code_hash' => Hash::make('654321'),
            'expires_at' => now()->addMinute(),
        ]);
    }

    public function test_prune_command_deletes_only_expired_challenges(): void
    {
        foreach ([
            ['gmail' => 'expired@gmail.com', 'expires_at' => now()->subSecond()],
            ['gmail' => 'valid@gmail.com', 'expires_at' => now()->addMinute()],
        ] as $challenge) {
            RegistrationVerification::query()->create([
                ...$challenge,
                'code' => '123456',
                'code_hash' => Hash::make('123456'),
            ]);
        }

        $this->artisan('verification:prune')->assertSuccessful();

        $this->assertDatabaseMissing('registration_verifications', ['gmail' => 'expired@gmail.com']);
        $this->assertDatabaseHas('registration_verifications', ['gmail' => 'valid@gmail.com']);
    }

    public function test_registration_flow_never_modifies_legacy_users(): void
    {
        Schema::create('legacy_users', function ($table): void {
            $table->id();
            $table->string('gmail');
        });
        DB::table('legacy_users')->insert([
            ['gmail' => 'legacy1@gmail.com'],
            ['gmail' => 'legacy2@gmail.com'],
            ['gmail' => 'legacy3@gmail.com'],
        ]);

        $this->post(route('register.store'), self::REGISTRATION);
        $this->post(route('verification.store'), ['code' => $this->currentCode()]);

        $this->assertDatabaseCount('legacy_users', 3);
    }

    private function currentCode(): string
    {
        return RegistrationVerification::query()->sole()->code;
    }
}
