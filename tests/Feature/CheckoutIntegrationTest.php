<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Client;
use App\Models\Product;
use App\Models\Gateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use App\Enums\UserRole;
use App\Enums\TransactionStatus;
use App\Enums\DocumentType;
use Tests\TestCase;

class CheckoutIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Client $client;
    private Product $product1;
    private Product $product2;
    private Gateway $gateway1;
    private Gateway $gateway2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::ADMIN,
        ]);

        $this->client = Client::create([
            'name' => 'John Doe',
            'email' => 'john@test.com',
            'document' => '12345678909',
            'document_type' => DocumentType::CPF,
        ]);

        $this->product1 = Product::create([
            'name' => 'Plano Basic',
            'amount' => 5000,
            'is_active' => true,
        ]);

        $this->product2 = Product::create([
            'name' => 'Plano Premium',
            'amount' => 15000,
            'is_active' => true,
        ]);

        $this->gateway1 = Gateway::create([
            'name' => 'Gateway 1',
            'driver' => 'gateway1',
            'priority' => 1,
            'is_active' => true,
        ]);

        $this->gateway2 = Gateway::create([
            'name' => 'Gateway 2',
            'driver' => 'gateway2',
            'priority' => 2,
            'is_active' => true,
        ]);
    }

    public function test_checkout_with_multiple_products_succeeds_on_primary_gateway()
    {
        Http::fake([
            'http://gateways-mock:3001/login' => Http::response(['token' => 'fake_token'], 200),
            'http://gateways-mock:3001/transactions' => Http::response(['id' => 'txn_abc123'], 200),
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/checkout', [
            'client_id' => $this->client->id,
            'credit_card' => '5569000000006063',
            'cvv' => '010',
            'products' => [
                ['id' => $this->product1->id, 'quantity' => 2],  // 2 x R$50 = R$100
                ['id' => $this->product2->id, 'quantity' => 1],  // 1 x R$150 = R$150
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('transactions', [
            'status' => 'PAID',
            'gateway_id' => $this->gateway1->id,
            'amount' => 25000, // R$250,00
            'external_id' => 'txn_abc123',
            'card_last_numbers' => '6063',
        ]);
        $this->assertDatabaseCount('transaction_products', 2);
    }

    public function test_checkout_falls_back_to_second_gateway_on_primary_failure()
    {
        Http::fake([
            'http://gateways-mock:3001/login' => Http::response(['token' => 'fake_token'], 200),
            'http://gateways-mock:3001/transactions' => Http::response(['message' => 'Card declined'], 400),
            'http://gateways-mock:3002/transacoes' => Http::response(['id' => 'txn_gw2_fallback'], 200),
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/checkout', [
            'client_id' => $this->client->id,
            'credit_card' => '5569000000006063',
            'cvv' => '010',
            'products' => [
                ['id' => $this->product1->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('transactions', [
            'status' => 'PAID',
            'gateway_id' => $this->gateway2->id,
            'external_id' => 'txn_gw2_fallback',
        ]);
    }

    public function test_checkout_records_failure_when_all_gateways_fail()
    {
        Http::fake([
            'http://gateways-mock:3001/login' => Http::response(['token' => 'fake_token'], 200),
            'http://gateways-mock:3001/transactions' => Http::response(['message' => 'Error'], 500),
            'http://gateways-mock:3002/transacoes' => Http::response(['message' => 'Error'], 500),
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/checkout', [
            'client_id' => $this->client->id,
            'credit_card' => '5569000000006063',
            'cvv' => '010',
            'products' => [
                ['id' => $this->product1->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('transactions', [
            'status' => TransactionStatus::FAILED,
        ]);
    }

    public function test_checkout_validates_required_fields()
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/checkout', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['client_id', 'credit_card', 'cvv', 'products']);
    }

    public function test_checkout_validates_product_exists()
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/checkout', [
            'client_id' => $this->client->id,
            'credit_card' => '5569000000006063',
            'cvv' => '010',
            'products' => [
                ['id' => 'non-existent-uuid', 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('products.0.id');
    }

    public function test_client_show_includes_transactions()
    {
        Http::fake([
            'http://gateways-mock:3001/login' => Http::response(['token' => 'fake_token'], 200),
            'http://gateways-mock:3001/transactions' => Http::response(['id' => 'txn_for_client'], 200),
        ]);

        // Create a transaction first
        $this->actingAs($this->admin, 'sanctum')->postJson('/api/transactions', [
            'client_id' => $this->client->id,
            'credit_card' => '5569000000006063',
            'cvv' => '010',
            'products' => [
                ['id' => $this->product1->id, 'quantity' => 1],
            ],
        ]);

        // Now check client detail includes transactions
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/clients/{$this->client->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['transactions']]);
    }
}
