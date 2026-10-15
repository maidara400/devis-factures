<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Clients
 */
class ClientController extends Controller
{
    /**
     * Lister les clients
     *
     * Paginé (15 par page). Le paramètre `search` filtre sur le nom, l'email ou le téléphone.
     *
     * @queryParam search string Terme de recherche.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $clients = Client::query()
            ->withCount('documents')
            ->search($request->query('search'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return ClientResource::collection($clients);
    }

    /**
     * Créer un client
     */
    public function store(ClientRequest $request): JsonResponse
    {
        $client = Client::create($request->validated());

        return (new ClientResource($client))->response()->setStatusCode(201);
    }

    /**
     * Afficher un client
     */
    public function show(Client $client): ClientResource
    {
        return new ClientResource($client->loadCount('documents'));
    }

    /**
     * Modifier un client
     */
    public function update(ClientRequest $request, Client $client): ClientResource
    {
        $client->update($request->validated());

        return new ClientResource($client);
    }

    /**
     * Supprimer un client
     *
     * Suppression logique : l'historique des devis et factures est conservé.
     */
    public function destroy(Client $client): JsonResponse
    {
        $client->delete();

        return response()->json(null, 204);
    }
}
