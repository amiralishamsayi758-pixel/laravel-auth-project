<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
        $response = $this->post(route('register.store'), self::REGISTRATION);

        $response->assertSessionHasNoErrors()
            ->assertSessionHas('registration.gmail', self::REGISTRATION['gmail'])
            ->assertSessionHas('registration.password_hash')
            ->assertSessionMissing('registration.password')
            ->assertSessionMissing('registration.password_confirmation')
            ->assertRedirectToRoute('verification.create');

        $hash = session('registration.password_hash');
        $this->assertIsString($hash);
        $this->assertNotSame(self::PASSWORD, $hash);
        $this->assertTrue(Hash::check(self::PASSWORD, $hash));
        $this->assertDatabaseCount('users', 0);
    }

    public function test_verification_requires_complete_temporary_registration_data(): void
    {
        $this->withSession(['registration' => array_diff_key(self::REGISTRATION, array_flip(['password', 'password_confirmation']))])
            ->post(route('verification.store'), ['code' => '123456'])
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

    public function test_verification_creates_unverified_user_with_same_hash_logs_in_and_clears_temporary_data(): void
    {
        $this->post(route('register.store'), self::REGISTRATION);
        $temporaryHash = session('registration.password_hash');

        $this->post(route('verification.store'), ['code' => '123456'])
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('registration')
            ->assertSessionMissing('verification.completed')
            ->assertRedirectToRoute('verification.notice');

        $user = User::query()->sole();
        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->gmail_verified_at);
        $this->assertSame($temporaryHash, $user->getRawOriginal('password'));
        $this->assertTrue(Hash::check(self::PASSWORD, $user->password));
    }

    public function test_verification_without_registration_redirects_to_register(): void
    {
        $this->get(route('verification.create'))->assertRedirectToRoute('register.create');
        $this->post(route('verification.store'), ['code' => '123456'])->assertRedirectToRoute('register.create');
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
        $this->post(route('verification.store'), ['code' => '123456'])->assertRedirectToRoute('verification.notice');
        $this->post(route('logout'));
        $this->post(route('verification.store'), ['code' => '123456'])->assertRedirectToRoute('register.create');

        $this->assertDatabaseCount('users', 1);
    }
}
