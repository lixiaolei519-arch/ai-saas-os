<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_login_read_profile_and_logout(): void
    {
        $register = $this->postJson('/api/v1/auth/register', [
            'name' => 'Grace Hopper',
            'email' => 'grace@example.com',
            'password' => 'password123',
        ])
            ->assertCreated()
            ->assertJsonPath('data.user.email', 'grace@example.com')
            ->json('data');

        $this->assertNotEmpty($register['token']);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'grace@example.com',
            'password' => 'password123',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.email', 'grace@example.com')
            ->json('data');

        $this->withToken($login['token'])
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'grace@example.com');

        $this->withToken($login['token'])
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('data.logged_out', true);

        $this->withToken($login['token'])
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }
}
