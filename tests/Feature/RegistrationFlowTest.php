<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    private const REGISTRATION = [
        'gmail' => 'learner@gmail.com',
        'phone' => '09123456789',
        'username' => 'learner_1',
    ];

    public function test_registration_page_loads(): void
    {
        $this->withoutVite();

        $this->get(route('register.create'))->assertOk();
    }

    public function test_valid_registration_is_stored_in_session_without_creating_a_user(): void
    {
        $this->post(route('register.store'), self::REGISTRATION)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('registration.gmail', self::REGISTRATION['gmail'])
            ->assertSessionHas('registration.phone', self::REGISTRATION['phone'])
            ->assertSessionHas('registration.username', self::REGISTRATION['username'])
            ->assertSessionMissing('registered_user_id')
            ->assertRedirectToRoute('verification.create');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_invalid_verification_code_is_rejected(): void
    {
        $this->withSession(['registration' => self::REGISTRATION])
            ->from(route('verification.create'))
            ->post(route('verification.store'), ['code' => '654321'])
            ->assertRedirectToRoute('verification.create')
            ->assertSessionHasErrors(['code']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_verification_without_registration_session_redirects_to_register(): void
    {
        $this->get(route('verification.create'))
            ->assertRedirectToRoute('register.create');

        $this->post(route('verification.store'), ['code' => '123456'])
            ->assertRedirectToRoute('register.create');
    }

    public function test_valid_verification_creates_the_verified_user_and_replaces_temporary_session(): void
    {
        $this->withSession(['registration' => self::REGISTRATION])
            ->post(route('verification.store'), ['code' => '123456'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('registered_user_id')
            ->assertSessionMissing('registration')
            ->assertSessionMissing('verification.completed')
            ->assertRedirectToRoute('dashboard');

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', self::REGISTRATION);

        $user = User::query()->sole();

        $this->assertNotNull($user->gmail_verified_at);
        $this->assertSame(self::REGISTRATION['gmail'], $user->gmail);
        $this->assertSame(self::REGISTRATION['phone'], $user->phone);
        $this->assertSame(self::REGISTRATION['username'], $user->username);
    }

    public function test_dashboard_redirects_without_registered_user_id(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirectToRoute('register.create');
    }

    public function test_dashboard_redirects_and_clears_a_nonexistent_registered_user_id(): void
    {
        $this->withSession(['registered_user_id' => 999999])
            ->get(route('dashboard'))
            ->assertRedirectToRoute('register.create')
            ->assertSessionMissing('registered_user_id');
    }

    public function test_dashboard_displays_persisted_user_data(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();

        $this->withSession(['registered_user_id' => $user->getKey()])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($user->gmail)
            ->assertSee($user->phone)
            ->assertSee($user->username)
            ->assertSee('تأیید شده')
            ->assertSee($user->created_at->format('Y-m-d H:i'));
    }

    public function test_duplicate_gmail_is_rejected(): void
    {
        $existingUser = User::factory()->create();

        $this->post(route('register.store'), [
            ...self::REGISTRATION,
            'gmail' => $existingUser->gmail,
        ])->assertSessionHasErrors(['gmail']);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_duplicate_phone_is_rejected(): void
    {
        $existingUser = User::factory()->create();

        $this->post(route('register.store'), [
            ...self::REGISTRATION,
            'phone' => $existingUser->phone,
        ])->assertSessionHasErrors(['phone']);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_duplicate_username_is_rejected(): void
    {
        $existingUser = User::factory()->create();

        $this->post(route('register.store'), [
            ...self::REGISTRATION,
            'username' => $existingUser->username,
        ])->assertSessionHasErrors(['username']);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_repeated_verification_submission_does_not_create_a_duplicate_user(): void
    {
        $this->withSession(['registration' => self::REGISTRATION])
            ->post(route('verification.store'), ['code' => '123456'])
            ->assertRedirectToRoute('dashboard');

        $this->post(route('verification.store'), ['code' => '123456'])
            ->assertRedirectToRoute('register.create');

        $this->assertDatabaseCount('users', 1);
    }
}
