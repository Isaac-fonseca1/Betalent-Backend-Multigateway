<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Transaction;
use App\Services\Checkout\CheckoutProcessor;
use App\Services\Gateways\GatewayFactory;
use App\Http\Resources\TransactionResource;
use App\Enums\TransactionStatus;
use App\Exceptions\RefundFailedException;
use Illuminate\Support\Facades\Gate;

class TransactionController extends Controller
{
    private CheckoutProcessor $checkout;

    public function __construct(CheckoutProcessor $checkout)
    {
        $this->checkout = $checkout;
    }

    public function index()
    {
        Gate::authorize('viewAny', Transaction::class);
        return TransactionResource::collection(Transaction::with(['client', 'gateway', 'transactionProducts.product'])->paginate(15));
    }

    public function store(CheckoutRequest $request)
    {
        // Public route: no auth required
        $transaction = $this->checkout->process($request->validated());

        return (new TransactionResource($transaction))
            ->response()
            ->setStatusCode($transaction->status === TransactionStatus::PAID ? 201 : 400);
    }

    public function show(Transaction $transaction)
    {
        Gate::authorize('view', $transaction);
        return new TransactionResource($transaction->load(['client', 'gateway', 'transactionProducts.product']));
    }

    public function refund(Transaction $transaction)
    {
        Gate::authorize('refund', $transaction);

        if ($transaction->status !== TransactionStatus::PAID) {
            throw new RefundFailedException('Apenas transações PAGAS podem ser estornadas.');
        }

        if (!$transaction->gateway_id) {
            throw new RefundFailedException('Não há gateway associado a esta transação para processar o estorno.');
        }

        if (empty($transaction->external_id)) {
            throw new RefundFailedException('Esta transação não possui um ID externo no gateway para processar o estorno.');
        }

        $driver = GatewayFactory::make($transaction->gateway->driver);
        $response = $driver->refund($transaction->external_id);

        if ($response['status'] !== 'sucesso') {
            throw new \App\Exceptions\RefundFailedException('O gateway recusou o estorno: ' . ($response['mensagem'] ?? 'Erro desconhecido.'));
        }

        $transaction->update(['status' => TransactionStatus::REFUNDED]);

        return new TransactionResource($transaction);
    }
}
