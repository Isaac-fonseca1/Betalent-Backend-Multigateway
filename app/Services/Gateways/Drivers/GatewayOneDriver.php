<?php

namespace App\Services\Gateways\Drivers;

use App\Services\Gateways\Contracts\PaymentGatewayContract;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class GatewayOneDriver implements PaymentGatewayContract
{
    private string $baseUrl;

    public function __construct()
    {
        // Gateway 1 runs on port 3001 inside Docker network
        $this->baseUrl = 'http://gateways-mock:3001';
    }

    /**
     * Authenticate with Gateway 1 and cache the Bearer token.
     */
    private function getAuthToken(): string
    {
        return Cache::remember('gateway1_auth_token', 3500, function () {
            $response = Http::post("{$this->baseUrl}/login", [
                'email' => 'dev@betalent.tech',
                'token' => 'FEC9BB078BF338F464F96B48089EB498',
            ]);

            return $response->json('token', '');
        });
    }

    /**
     * README Gateway 1 fields: amount (cents), name, email, cardNumber, cvv
     * Endpoint: POST /transactions
     */
    public function charge(float $amount, string $clientName, string $clientEmail, string $cardNumber, string $cvv): array
    {
        $token = $this->getAuthToken();

        $response = Http::withToken($token)->post("{$this->baseUrl}/transactions", [
            'amount' => (int) round($amount * 100), // Convert Reais to centavos
            'name' => $clientName,
            'email' => $clientEmail,
            'cardNumber' => $cardNumber,
            'cvv' => $cvv,
        ]);

        if ($response->successful()) {
            return [
                'status' => 'sucesso',
                'id' => $response->json('id'),
            ];
        }

        return [
            'status' => 'falha',
            'message' => $response->json('message', 'Erro desconhecido'),
        ];
    }

    /**
     * README Gateway 1 refund: POST /transactions/:id/charge_back
     */
    public function refund(string $transactionId): array
    {
        $token = $this->getAuthToken();

        $response = Http::withToken($token)->post("{$this->baseUrl}/transactions/{$transactionId}/charge_back");

        if ($response->successful()) {
            return ['status' => 'sucesso'];
        }

        return [
            'status' => 'falha',
            'message' => $response->json('message', 'Erro desconhecido'),
        ];
    }
}
