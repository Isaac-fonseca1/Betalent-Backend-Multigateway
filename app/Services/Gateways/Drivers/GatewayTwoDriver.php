<?php

namespace App\Services\Gateways\Drivers;

use App\Services\Gateways\Contracts\PaymentGatewayContract;
use Illuminate\Support\Facades\Http;

class GatewayTwoDriver implements PaymentGatewayContract
{
    private string $baseUrl;

    public function __construct()
    {
        // Gateway 2 runs on port 3002 inside Docker network
        $this->baseUrl = 'http://gateways-mock:3002';
    }

    /**
     * Gateway 2 authenticates via headers: Gateway-Auth-Token + Gateway-Auth-Secret
     */
    private function httpClient()
    {
        return Http::withHeaders([
            'Gateway-Auth-Token' => 'tk_f2198cc671b5289fa856',
            'Gateway-Auth-Secret' => '3d15e8ed6131446ea7e3456728b1211f',
        ]);
    }

    /**
     * README Gateway 2 fields: valor (cents), nome, email, numeroCartao, cvv
     * Endpoint: POST /transacoes
     */
    public function charge(float $amount, string $clientName, string $clientEmail, string $cardNumber, string $cvv): array
    {
        $response = $this->httpClient()->post("{$this->baseUrl}/transacoes", [
            'valor' => (int) round($amount * 100), // Value in centavos (same as Gateway 1)
            'nome' => $clientName,
            'email' => $clientEmail,
            'numeroCartao' => $cardNumber,
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
     * README Gateway 2 refund: POST /transacoes/reembolso with {"id": "..."}
     */
    public function refund(string $transactionId): array
    {
        $response = $this->httpClient()->post("{$this->baseUrl}/transacoes/reembolso", [
            'id' => $transactionId,
        ]);

        if ($response->successful()) {
            return ['status' => 'sucesso'];
        }

        return [
            'status' => 'falha',
            'message' => $response->json('message', 'Erro desconhecido'),
        ];
    }
}
