<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_get_routes_return_successful_responses(): void
    {
        $this->withoutVite();

        foreach (['/', '/register', '/verify', '/dashboard'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_valid_registration_data_redirects_to_verification(): void
    {
        $response = $this->post(route('register.store'), [
            'gmail' => 'learner@gmail.com',
            'phone' => '09123456789',
            'username' => 'learner',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute('verification.create');
    }

    public function test_invalid_registration_data_returns_validation_errors(): void
    {
        $response = $this
            ->from(route('register.create'))
            ->post(route('register.store'), [
                'gmail' => 'learner@example.com',
                'phone' => '123',
                'username' => 'ab',
            ]);

        $response
            ->assertRedirectToRoute('register.create')
            ->assertSessionHasErrors(['gmail', 'phone', 'username']);
    }

    public function test_valid_verification_code_redirects_to_dashboard(): void
    {
        $response = $this->post(route('verification.store'), [
            'code' => '123456',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute('dashboard');
    }

    public function test_invalid_verification_code_returns_validation_errors(): void
    {
        $response = $this
            ->from(route('verification.create'))
            ->post(route('verification.store'), [
                'code' => '1234',
            ]);

        $response
            ->assertRedirectToRoute('verification.create')
            ->assertSessionHasErrors(['code']);
    }
}
