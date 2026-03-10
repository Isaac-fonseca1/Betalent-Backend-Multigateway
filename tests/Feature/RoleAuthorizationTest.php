<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Gateway;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use App\Enums\UserRole;
use App\Enums\TransactionStatus;
use App\Enums\DocumentType;
use Tests\TestCase;
use App\Models\Transaction;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::create([
            'name' => "{$role} User",
            'email' => strtolower($role) . '@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::from($role),
        ]);
    }

    // ───────────────────────────────────────────
    // PRODUCT MANAGEMENT: ADMIN, MANAGER, FINANCE can CRUD
    // ───────────────────────────────────────────

    public function test_admin_can_create_product()
    {
        $user = $this->makeUser('ADMIN');
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/products', [
            'name' => 'Plano Premium',
            'amount' => 9900,
        ]);
        $response->assertStatus(201);
    }

    public function test_manager_can_create_product()
    {
        $user = $this->makeUser('MANAGER');
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/products', [
            'name' => 'Plano Basic',
            'amount' => 4900,
        ]);
        $response->assertStatus(201);
    }

    public function test_finance_can_create_product()
    {
        $user = $this->makeUser('FINANCE');
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/products', [
            'name' => 'Plano Starter',
            'amount' => 2900,
        ]);
        $response->assertStatus(201);
    }

    public function test_user_cannot_create_product()
    {
        $user = $this->makeUser('USER');
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/products', [
            'name' => 'Plano Hacker',
            'amount' => 100,
        ]);
        $response->assertStatus(403);
    }

    public function test_user_can_view_products()
    {
        $user = $this->makeUser('USER');
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/products');
        $response->assertStatus(200);
    }

    // ───────────────────────────────────────────
    // USER MANAGEMENT: ADMIN and MANAGER can CRUD
    // ───────────────────────────────────────────

    public function test_admin_can_list_users()
    {
        // We don't have an explicit users CRUD controller yet,
        // but UserPolicy is registered — this test validates the policy logic directly.
        $admin = $this->makeUser('ADMIN');
        $this->assertTrue($admin->can('viewAny', User::class));
    }

    public function test_manager_can_manage_users()
    {
        $manager = $this->makeUser('MANAGER');
        $this->assertTrue($manager->can('create', User::class));
    }

    public function test_finance_cannot_manage_users()
    {
        $finance = $this->makeUser('FINANCE');
        $this->assertFalse($finance->can('create', User::class));
    }

    public function test_user_cannot_manage_users()
    {
        $user = $this->makeUser('USER');
        $this->assertFalse($user->can('create', User::class));
    }

    // ───────────────────────────────────────────
    // GATEWAY MANAGEMENT: Only ADMIN
    // ───────────────────────────────────────────

    public function test_admin_can_create_gateway()
    {
        $admin = $this->makeUser('ADMIN');
        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/gateways', [
            'name' => 'Test GW',
            'driver' => 'test_driver',
            'priority' => 1,
            'is_active' => true,
        ]);
        $response->assertStatus(201);
    }

    public function test_manager_cannot_create_gateway()
    {
        $manager = $this->makeUser('MANAGER');
        $response = $this->actingAs($manager, 'sanctum')->postJson('/api/gateways', [
            'name' => 'Hacked GW',
            'driver' => 'hacked',
        ]);
        $response->assertStatus(403);
    }

    public function test_finance_can_view_gateways()
    {
        $finance = $this->makeUser('FINANCE');
        $response = $this->actingAs($finance, 'sanctum')->getJson('/api/gateways');
        $response->assertStatus(200);
    }

    // ───────────────────────────────────────────
    // REFUND: Only ADMIN and FINANCE
    // ───────────────────────────────────────────

    public function test_admin_can_refund()
    {
        $admin = $this->makeUser('ADMIN');
        $client = Client::create(['name' => 'C', 'email' => 'c@t.com', 'document' => '111', 'document_type' => DocumentType::CPF]);
        $gateway = Gateway::create(['name' => 'GW', 'driver' => 'gateway1', 'priority' => 1, 'is_active' => true]);
        $transaction = Transaction::create([
            'client_id' => $client->id,
            'gateway_id' => $gateway->id,
            'external_id' => 'ext_123',
            'status' => 'PAID',
            'amount' => 1000,
            'card_last_numbers' => '1234',
        ]);

        $this->assertTrue($admin->can('refund', $transaction));
    }

    public function test_finance_can_refund()
    {
        $finance = $this->makeUser('FINANCE');
        $client = Client::create(['name' => 'C', 'email' => 'c2@t.com', 'document' => '222', 'document_type' => DocumentType::CPF]);
        $gateway = Gateway::create(['name' => 'GW2', 'driver' => 'gateway2', 'priority' => 2, 'is_active' => true]);
        $transaction = Transaction::create([
            'client_id' => $client->id,
            'gateway_id' => $gateway->id,
            'external_id' => 'ext_456',
            'status' => 'PAID',
            'amount' => 2000,
            'card_last_numbers' => '5678',
        ]);

        $this->assertTrue($finance->can('refund', $transaction));
    }

    public function test_manager_cannot_refund()
    {
        $manager = $this->makeUser('MANAGER');
        $client = Client::create(['name' => 'C', 'email' => 'c3@t.com', 'document' => '333', 'document_type' => DocumentType::CPF]);
        $transaction = Transaction::create([
            'client_id' => $client->id,
            'status' => 'PAID',
            'amount' => 3000,
            'card_last_numbers' => '9999',
        ]);

        $this->assertFalse($manager->can('refund', $transaction));
    }

    public function test_user_cannot_refund()
    {
        $regularUser = $this->makeUser('USER');
        $client = Client::create(['name' => 'C', 'email' => 'c4@t.com', 'document' => '444', 'document_type' => DocumentType::CPF]);
        $transaction = Transaction::create([
            'client_id' => $client->id,
            'status' => 'PAID',
            'amount' => 4000,
            'card_last_numbers' => '0000',
        ]);

        $this->assertFalse($regularUser->can('refund', $transaction));
    }
}
