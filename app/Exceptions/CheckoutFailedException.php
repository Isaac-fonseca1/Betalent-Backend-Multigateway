<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class CheckoutFailedException extends Exception
{
    public function render($request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage() ?: 'Falha no processamento do checkout.',
            'error_code' => 'CHECKOUT_FAILED'
        ], 422);
    }
}
