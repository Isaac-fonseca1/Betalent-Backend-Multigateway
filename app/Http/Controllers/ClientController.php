<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Support\Facades\Gate;
use App\Http\Resources\ClientResource;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Requests\StoreClientRequest;

class ClientController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Client::class);
        return ClientResource::collection(Client::paginate(15));
    }

    public function store(StoreClientRequest $request)
    {
        $client = Client::create($request->validated());
        return new ClientResource($client);
    }

    public function show(Client $client)
    {
        Gate::authorize('view', $client);
        // README: "Detalhe do cliente e todas suas compras"
        return new ClientResource($client->load('transactions.transactionProducts.product'));
    }

    public function update(UpdateClientRequest $request, Client $client)
    {
        $client->update($request->validated());
        return new ClientResource($client);
    }

    public function destroy(Client $client)
    {
        Gate::authorize('delete', $client);
        $client->delete();
        return response()->json(null, 204);
    }
}
