<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Gateway;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Enums\UserRole;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Admin User
        User::factory()->create([
            'name' => 'Admin Test',
            'email' => 'dev@betalent.tech',
            'password' => Hash::make('password'),
            'role' => UserRole::ADMIN,
        ]);

        // 2. Create Gateways required by the Test
        Gateway::factory()->create([
            'name' => 'Gateway 1',
            'driver' => 'gateway1',
            'priority' => 1,
        ]);

        Gateway::factory()->create([
            'name' => 'Gateway 2',
            'driver' => 'gateway2',
            'priority' => 2,
        ]);

        // 3. Create Sample Products
        Product::factory()->create([
            'name' => 'Plano Premium Mensal',
            'description' => 'Acesso premium por 1 mês.',
            'amount' => 9990, // R$ 99,90
        ]);

        Product::factory()->create([
            'name' => 'Taxa de Setup',
            'description' => 'Taxa única de configuração',
            'amount' => 15000, // R$ 150,00
        ]);

        // 4. Create Sample Client
        Client::factory()->create([
            'name' => 'João Silva',
            'email' => 'joao.silva@teste.com',
            'document' => '12345678909',
            'document_type' => 'CPF',
        ]);

        // 5. Create some random data for testing
        User::factory()->count(5)->create();
        Product::factory()->count(10)->create();
        Client::factory()->count(10)->create();
    }
}
