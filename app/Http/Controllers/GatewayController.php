<?php

namespace App\Http\Controllers;

use App\Models\Gateway;
use Illuminate\Support\Facades\Gate;
use App\Http\Resources\GatewayResource;
use App\Http\Requests\UpdateGatewayRequest;
use App\Http\Requests\StoreGatewayRequest;

class GatewayController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Gateway::class);
        return GatewayResource::collection(Gateway::orderBy('priority', 'asc')->get());
    }

    public function store(StoreGatewayRequest $request)
    {
        $gateway = Gateway::create($request->validated());
        return new GatewayResource($gateway);
    }

    public function show(Gateway $gateway)
    {
        Gate::authorize('view', $gateway);
        return new GatewayResource($gateway);
    }

    public function update(UpdateGatewayRequest $request, Gateway $gateway)
    {
        $gateway->update($request->validated());
        return new GatewayResource($gateway);
    }

    public function destroy(Gateway $gateway)
    {
        Gate::authorize('delete', $gateway);
        $gateway->delete();
        return response()->json(null, 204);
    }
}
