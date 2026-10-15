<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Gestion des utilisateurs et de leurs rôles (permission `users.manage`, réservée aux administrateurs).
 *
 * @tags Utilisateurs
 */
class UserController extends Controller
{
    /**
     * Lister les utilisateurs
     */
    public function index(): AnonymousResourceCollection
    {
        return UserResource::collection(User::orderBy('name')->paginate(15));
    }

    /**
     * Créer un utilisateur
     */
    public function store(UserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    /**
     * Afficher un utilisateur
     */
    public function show(User $user): UserResource
    {
        return new UserResource($user);
    }

    /**
     * Modifier un utilisateur
     *
     * Un administrateur ne peut pas se rétrograder ni se désactiver lui-même.
     */
    public function update(UserRequest $request, User $user): UserResource|JsonResponse
    {
        $data = array_filter($request->validated(), fn ($v) => $v !== null);

        if ($user->is(auth()->user()) && (
            (isset($data['role']) && $data['role'] !== $user->role->value) || ($data['is_active'] ?? true) === false
        )) {
            return response()->json(['message' => 'Vous ne pouvez pas modifier votre propre rôle ou statut.'], 422);
        }

        $user->update($data);

        return new UserResource($user->refresh());
    }

    /**
     * Supprimer un utilisateur
     */
    public function destroy(User $user): JsonResponse
    {
        if ($user->is(auth()->user())) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer votre propre compte.'], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(null, 204);
    }
}
