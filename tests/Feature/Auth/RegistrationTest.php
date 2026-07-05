<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_fortify_registration_route_is_not_available(): void
    {
        $response = $this->get('/register');

        $response->assertNotFound();
    }
}
