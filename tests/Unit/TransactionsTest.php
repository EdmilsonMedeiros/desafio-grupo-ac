<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class TransactionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_successfully()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($user)->postJson('api/transactions/deposit', [
            'email_beneficiary' => 'test@example.com',
            'amount' => 100,
            'payer_name' => 'Test User',
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'message' => 'Depósito realizado com sucesso',
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'amount' => 100,
            'type' => 'deposit',
            'status' => 'success',
            'beneficiary_id' => $user->id,
            'payer_name' => 'Test User',
        ]);
    }

    public function test_deposit_with_invalid_data()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($user)->postJson('api/transactions/deposit', [
            'email_beneficiary' => 'test@example',
            'amount' => 100,
            'payer_name' => 'Test User',
        ]);

        $response->assertStatus(422);

        $response->assertJson([
            'message' => 'Validação falhou',
        ]);

        $this->assertDatabaseMissing('transactions', [
            'user_id' => $user->id,
            'amount' => 100,
            'type' => 'deposit',
            'status' => 'success',
            'beneficiary_id' => $user->id,
            'payer_name' => 'Test User',
        ]);
    }

    public function test_transfer_successfully()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $user2 = User::create([
            'name' => 'Test User 2',
            'email' => 'test2@example.com',
            'password' => bcrypt('password'),
        ]);

        $user2Auth = $this->actingAs($user2);

        $response = $user2Auth->postJson('api/transactions/transfer', [
            'email_beneficiary' => 'test@example.com',
            'amount' => 100,
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'message' => 'Transferência realizada com sucesso',
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'amount' => 100,
            'type' => 'receive',
            'status' => 'success',
            'beneficiary_id' => $user->id,
            'payer_name' => 'Test User 2',
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user2->id,
            'amount' => 100,
            'type' => 'transfer',
            'status' => 'success',
            'beneficiary_id' => $user->id,
            'payer_name' => 'Test User 2',
        ]);
    }

    public function test_transfer_with_invalid_data()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $user2 = User::create([
            'name' => 'Test User 2',
            'email' => 'test2@example.com',
            'password' => bcrypt('password'),
        ]);

        $user2Auth = $this->actingAs($user2);

        $response = $user2Auth->postJson('api/transactions/transfer', [
            'email_beneficiary' => 'test@example',
            'amount' => 100,
        ]);

        $response->assertStatus(422);

        $response->assertJson([
            'message' => 'Validação falhou',
        ]);

        $this->assertDatabaseMissing('transactions', [
            'user_id' => $user->id,
            'amount' => 100,
            'type' => 'receive',
            'status' => 'success',
            'beneficiary_id' => $user->id,
            'payer_name' => 'Test User 2',
        ]);

        $this->assertDatabaseMissing('transactions', [
            'user_id' => $user2->id,
            'amount' => 100,
            'type' => 'transfer',
            'status' => 'success',
            'beneficiary_id' => $user->id,
            'payer_name' => 'Test User 2',
        ]);
    }

    public function test_revert_transfer_successfully()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $user2 = User::create([
            'name' => 'Test User 2',
            'email' => 'test2@example.com',
            'password' => bcrypt('password'),
        ]);

        $user2Auth = $this->actingAs($user2);

        $response = $user2Auth->postJson('api/transactions/transfer', [
            'email_beneficiary' => 'test@example.com',
            'amount' => 100,
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'message' => 'Transferência realizada com sucesso',
        ]);

        $userAuth = $this->actingAs($user);

        $responseRevert = $userAuth->postJson('api/transactions/revert', [
            'transaction_id' => $user->transactions->last()->id,
        ]);

        $responseRevert->assertStatus(200);

        $responseRevert->assertJson([
            'message' => 'Transação revertida com sucesso',
        ]);
    }

    public function test_get_transactions_successfully()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $userAuth = $this->actingAs($user);

        $response = $userAuth->getJson('api/transactions');

        $response->assertStatus(200);
        $response->assertJsonStructure([]);
        $response->assertJsonCount(0);
    }
}
