<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class BalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_balance_successfully()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $userAuth = $this->actingAs($user);

        $response = $userAuth->getJson('api/balance');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'balance',
            "user_id",
            "created_at",
            "updated_at"
        ]);
        
    }
}
