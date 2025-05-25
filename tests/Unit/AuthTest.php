<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_user_successfully()
    {
        $response = $this->postJson('api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertJson([
            'message' => 'Usuário criado com sucesso',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }

    public function test_register_user_with_invalid_data()
    {
        $response = $this->postJson('api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422);

        $response->assertJson([
            'message' => 'Validação falhou',
        ]);
    }

    public function test_login_user_successfully()	
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'access_token',
            'token_type',
            'expires_in',
        ]);

        $this->assertAuthenticated();
    }

    public function test_login_user_with_invalid_data()
    {
        $response = $this->postJson('api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'invalid_password',
        ]);

        $response->assertStatus(401);

        $response->assertJson([
            "error" => "Unauthorized"
        ]);
    }
}
