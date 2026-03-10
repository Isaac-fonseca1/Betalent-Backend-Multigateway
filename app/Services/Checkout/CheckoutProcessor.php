<?php

namespace App\Services\Checkout;

use App\Models\Client;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\Gateway;
use App\Services\Gateways\GatewayFactory;
use App\Exceptions\CheckoutFailedException;
use App\Enums\TransactionStatus;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;

class CheckoutProcessor
{
    /**
     * Run a checkout purchase flow.
     * Attempt the primary gateway; if it fails, attempt the next by priority.
     */
    public function process(array $data): Transaction
    {
        // Resolve products and calculate totals
        $products = Product::whereIn('id', array_column($data['products'], 'id'))->get()->keyBy('id');

        $totalCents = 0;
        $transactionProductsData = [];

        foreach ($data['products'] as $item) {
            if (!$products->has($item['id'])) {
                throw new Exception("Product ID {$item['id']} not found or inactive.");
            }
            $product = $products->get($item['id']);
            $lineTotal = $product->amount * $item['quantity'];
            $totalCents += $lineTotal;

            $transactionProductsData[] = [
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'unit_price' => $product->amount,
            ];
        }

        $client = Client::findOrFail($data['client_id']);

        // Fetch gateways ordered by priority
        $gateways = Gateway::where('is_active', true)->orderBy('priority', 'asc')->get();

        if ($gateways->isEmpty()) {
            throw new CheckoutFailedException("Nenhum gateway de pagamento ativo disponível no momento.");
        }

        $success = false;
        $lastError = null;
        $usedGatewayId = null;
        $externalRef = null;

        foreach ($gateways as $gatewayModel) {
            try {
                $driver = GatewayFactory::make($gatewayModel->driver);

                Log::info("Attempting charge with Gateway: {$gatewayModel->name}");

                // Both gateways require: amount (float Reais), name, email, cardNumber, cvv
                $response = $driver->charge(
                    $totalCents / 100, // Convert cents to Reais
                    $client->name,
                    $client->email,
                    $data['credit_card'],
                    $data['cvv']
                );

                if ($response['status'] === 'sucesso') {
                    $usedGatewayId = $gatewayModel->id;
                    $externalRef = $response['id'] ?? null;
                    $success = true;
                    break;
                } else {
                    $lastError = $response['message'] ?? $response['mensagem'] ?? 'Gateway returned failure status';
                    Log::warning("Gateway {$gatewayModel->name} failed: {$lastError}");
                }
            } catch (Exception $e) {
                $lastError = $e->getMessage();
                Log::error("Gateway {$gatewayModel->name} threw exception: {$lastError}");
            }
        }

        // Persist transaction
        $transaction = DB::transaction(function () use ($client, $usedGatewayId, $externalRef, $success, $totalCents, $data, $lastError, $transactionProductsData) {
            $transaction = Transaction::create([
                'client_id' => $client->id,
                'gateway_id' => $usedGatewayId,
                'external_id' => $externalRef,
                'status' => $success ? TransactionStatus::PAID : TransactionStatus::FAILED,
                'amount' => $totalCents,
                'card_last_numbers' => substr($data['credit_card'], -4),
                'error_message' => $success ? null : $lastError,
            ]);

            foreach ($transactionProductsData as $productData) {
                $transaction->transactionProducts()->create($productData);
            }

            return $transaction;
        });

        if (!$success) {
            throw new CheckoutFailedException($lastError ?: "O pagamento foi recusado por todos os gateways disponíveis.");
        }

        return $transaction->load('transactionProducts.product');
    }
}
