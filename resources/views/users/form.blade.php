@php($editing = $user->exists)
<x-layouts.app :title="$editing ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur'">
    <x-page-header :title="$editing ? 'Modifier '.$user->name : 'Nouvel utilisateur'" :back="route('users.index')" />

    <form method="POST" action="{{ $editing ? route('users.update', $user) : route('users.store') }}" class="card max-w-2xl space-y-5 p-6 sm:p-8">
        @csrf
        @if($editing) @method('PUT') @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <x-input label="Nom complet" name="name" :value="$user->name" required />
            <x-input label="Adresse e-mail" name="email" type="email" :value="$user->email" required />
            <x-input class="sm:col-span-2" label="Mot de passe" name="password" type="password" :required="! $editing" autocomplete="new-password"
                     :hint="$editing ? 'Laissez vide pour conserver le mot de passe actuel. 8 caractères minimum avec lettres et chiffres.' : '8 caractères minimum avec lettres et chiffres.'" />

            <div class="sm:col-span-2">
                <label class="label">Rôle <span class="text-rose-500">*</span></label>
                <div class="grid gap-3 sm:grid-cols-3">
                    @foreach(\App\Enums\Role::cases() as $role)
                        <label class="cursor-pointer">
                            <input type="radio" name="role" value="{{ $role->value }}" class="peer sr-only" @checked(old('role', $user->role?->value ?? 'accountant') === $role->value)>
                            <div class="rounded-md border border-slate-200 p-4 text-sm transition peer-checked:border-brand-500 peer-checked:bg-brand-50 peer-checked:ring-4 peer-checked:ring-brand-500/10 dark:border-white/10 dark:peer-checked:bg-brand-500/10">
                                <p class="font-semibold">{{ $role->label() }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ count($role->permissions()) }} permissions</p>
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('role')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            </div>

            <label class="flex cursor-pointer items-center gap-2.5 text-sm sm:col-span-2">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active)) class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                Compte actif
            </label>
        </div>

        <div class="flex items-center justify-between border-t border-slate-100 pt-5 dark:border-white/5">
            @if($editing && ! $user->is(auth()->user()))
                <button form="delete-user" class="btn-danger" onclick="return confirm('Supprimer cet utilisateur ?')"><x-icon name="trash" class="size-4" /> Supprimer</button>
            @else <span></span> @endif
            <div class="flex gap-2">
                <a href="{{ route('users.index') }}" class="btn-secondary">Annuler</a>
                <button class="btn-primary"><x-icon name="check" class="size-4" /> Enregistrer</button>
            </div>
        </div>
    </form>
    @if($editing)
        <form id="delete-user" method="POST" action="{{ route('users.destroy', $user) }}" class="hidden">@csrf @method('DELETE')</form>
    @endif
</x-layouts.app>
