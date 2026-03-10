<?php

namespace App\Services\Gateways;

use App\Services\Gateways\Contracts\PaymentGatewayContract;
use App\Services\Gateways\Drivers\GatewayOneDriver;
use App\Services\Gateways\Drivers\GatewayTwoDriver;
use InvalidArgumentException;

class GatewayFactory
{
    /**
     * Resolve the Gateway instance by string driver name.
     */
    public static function make(string $driver): PaymentGatewayContract
    {
        return match ($driver) {
            'gateway1' => new GatewayOneDriver(),
            'gateway2' => new GatewayTwoDriver(),
            default => throw new InvalidArgumentException("Gateway driver [{$driver}] is not supported."),
        };
    }
}
