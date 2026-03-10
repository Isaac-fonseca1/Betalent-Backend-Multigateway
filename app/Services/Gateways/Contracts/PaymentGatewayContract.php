<?php

namespace App\Services\Gateways\Contracts;

interface PaymentGatewayContract
{
    /**
     * Process a payment charge.
     *
     * @param float $amount Amount in Reais (e.g. 10.00 for R$ 10,00)
     * @param string $clientName Buyer's name
     * @param string $clientEmail Buyer's email
     * @param string $cardNumber Full credit card number
     * @param string $cvv Card CVV
     * @return array ['status' => 'sucesso'|'falha', 'id' => '...', 'message' => '...']
     */
    public function charge(float $amount, string $clientName, string $clientEmail, string $cardNumber, string $cvv): array;

    /**
     * Process a refund.
     *
     * @param string $transactionId The external reference ID
     * @return array ['status' => 'sucesso'|'falha']
     */
    public function refund(string $transactionId): array;
}
