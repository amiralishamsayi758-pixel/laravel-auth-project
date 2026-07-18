<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_blade_preview_routes_return_successful_responses(): void
    {
        $this->withoutVite();

        foreach (['/', '/register', '/verify', '/dashboard'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }
}
