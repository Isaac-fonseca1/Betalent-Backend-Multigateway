<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class RefundFailedException extends Exception
{
    public function render($request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage() ?: 'Falha ao processar o estorno.',
            'error_code' => 'REFUND_FAILED'
        ], 422);
    }
}
