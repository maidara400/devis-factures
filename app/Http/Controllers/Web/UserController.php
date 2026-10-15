<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', ['users' => User::orderBy('name')->paginate(15)]);
    }

    public function create(): View
    {
        return view('users.form', ['user' => new User(['is_active' => true])]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        User::create([...$request->validated(), 'is_active' => $request->boolean('is_active')]);

        return redirect()->route('users.index')->with('success', 'Utilisateur créé.');
    }

    public function edit(User $user): View
    {
        return view('users.form', compact('user'));
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = array_filter($request->validated(), fn ($v) => $v !== null);
        $data['is_active'] = $request->boolean('is_active');

        if ($user->is(auth()->user()) && ($data['role'] !== $user->role->value || ! $data['is_active'])) {
            return back()->with('error', 'Vous ne pouvez pas modifier votre propre rôle ou statut.');
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'Utilisateur mis à jour.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->is(auth()->user())) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $user->tokens()->delete();
        $user->delete();

        return redirect()->route('users.index')->with('success', 'Utilisateur supprimé.');
    }
}
