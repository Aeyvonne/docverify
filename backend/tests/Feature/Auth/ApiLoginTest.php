<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_login_returns_token(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
                 ->assertJsonStructure(['access_token', 'token_type']);
    }

    public function test_api_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable();
    }

    public function test_api_login_fails_with_unknown_email(): void
    {
        $this->postJson('/api/login', [
            'email'    => 'nobody@docverify.sn',
            'password' => 'password',
        ])->assertUnprocessable();
    }
}
