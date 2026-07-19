<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    private const VALID_REGISTRATION = [
        'gmail' => 'learner@gmail.com',
        'phone' => '09123456789',
        'username' => 'learner_1',
    ];

    public function test_public_form_pages_return_successful_responses_in_the_expected_session_state(): void
    {
        $this->withoutVite();

        $this->get(route('home'))->assertOk();
        $this->get(route('register.create'))->assertOk();
        $this->withSession(['registration' => self::VALID_REGISTRATION])
            ->get(route('verification.create'))
            ->assertOk();
        $this->withSession([
            'registration' => self::VALID_REGISTRATION,
            'verification.completed' => true,
        ])->get(route('dashboard'))->assertOk();
    }

    public function test_register_requires_all_fields(): void
    {
        $this->from(route('register.create'))
            ->post(route('register.store'))
            ->assertRedirectToRoute('register.create')
            ->assertSessionHasErrors(['gmail', 'phone', 'username']);
    }

    public function test_register_rejects_an_invalid_gmail(): void
    {
        $this->post(route('register.store'), [
            ...self::VALID_REGISTRATION,
            'gmail' => 'Learner@example.com',
        ])->assertSessionHasErrors(['gmail']);
    }

    public function test_register_rejects_an_invalid_phone(): void
    {
        $this->post(route('register.store'), [
            ...self::VALID_REGISTRATION,
            'phone' => '08123456789',
        ])->assertSessionHasErrors(['phone']);
    }

    public function test_register_rejects_an_invalid_username(): void
    {
        $this->post(route('register.store'), [
            ...self::VALID_REGISTRATION,
            'username' => 'نام کاربری',
        ])->assertSessionHasErrors(['username']);
    }

    public function test_verify_redirects_to_register_without_registration_session(): void
    {
        $this->get(route('verification.create'))
            ->assertRedirectToRoute('register.create');

        $this->post(route('verification.store'), ['code' => '123456'])
            ->assertRedirectToRoute('register.create');
    }

    public function test_dashboard_redirects_to_register_without_completed_verification(): void
    {
        $this->withSession(['registration' => self::VALID_REGISTRATION])
            ->get(route('dashboard'))
            ->assertRedirectToRoute('register.create');
    }

    public function test_successful_register_stores_only_temporary_registration_data(): void
    {
        $this->post(route('register.store'), self::VALID_REGISTRATION)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('registration.gmail', self::VALID_REGISTRATION['gmail'])
            ->assertSessionHas('registration.phone', self::VALID_REGISTRATION['phone'])
            ->assertSessionHas('registration.username', self::VALID_REGISTRATION['username'])
            ->assertSessionMissing('verification.completed')
            ->assertRedirectToRoute('verification.create');
    }

    public function test_successful_verification_stores_completed_session_flag(): void
    {
        $this->withSession(['registration' => self::VALID_REGISTRATION])
            ->post(route('verification.store'), ['code' => '123456'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('verification.completed', true)
            ->assertRedirectToRoute('dashboard');
    }

    public function test_verification_rejects_an_invalid_code(): void
    {
        $this->withSession(['registration' => self::VALID_REGISTRATION])
            ->from(route('verification.create'))
            ->post(route('verification.store'), ['code' => '1234'])
            ->assertRedirectToRoute('verification.create')
            ->assertSessionHasErrors(['code']);
    }

    public function test_dashboard_displays_temporary_session_data(): void
    {
        $this->withoutVite();

        $this->withSession([
            'registration' => self::VALID_REGISTRATION,
            'verification.completed' => true,
        ])->get(route('dashboard'))
            ->assertOk()
            ->assertSee(self::VALID_REGISTRATION['gmail'])
            ->assertSee(self::VALID_REGISTRATION['phone'])
            ->assertSee(self::VALID_REGISTRATION['username']);
    }
}
