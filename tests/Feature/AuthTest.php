<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_with_valid_credentials()
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@betalent.test',
            'password' => Hash::make('secret123'),
            'role' => 'ADMIN',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@betalent.test',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'token_type', 'user' => ['id', 'name', 'email', 'role']])
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', 'admin@betalent.test')
            ->assertJsonPath('user.role', 'ADMIN');
    }

    public function test_login_fails_with_wrong_password()
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@betalent.test',
            'password' => Hash::make('secret123'),
            'role' => 'ADMIN',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@betalent.test',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_login_fails_with_nonexistent_email()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@test.com',
            'password' => 'any_password',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_requires_email_and_password()
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_protected_routes_return_401_without_token()
    {
        $this->getJson('/api/clients')->assertStatus(401);
        $this->getJson('/api/products')->assertStatus(401);
        $this->getJson('/api/gateways')->assertStatus(401);
        $this->getJson('/api/transactions')->assertStatus(401);
    }

    public function test_authenticated_user_can_access_protected_routes()
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@betalent.test',
            'password' => Hash::make('secret123'),
            'role' => 'ADMIN',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/clients')
            ->assertStatus(200);
    }
}
