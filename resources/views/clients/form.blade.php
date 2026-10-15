@php($editing = $client->exists)
<x-layouts.app :title="$editing ? 'Modifier le client' : 'Nouveau client'">
    <x-page-header :title="$editing ? 'Modifier '.$client->name : 'Nouveau client'" :back="route('clients.index')" />

    <form method="POST" action="{{ $editing ? route('clients.update', $client) : route('clients.store') }}" class="card max-w-3xl space-y-5 p-6 sm:p-8">
        @csrf
        @if($editing) @method('PUT') @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <x-input class="sm:col-span-2" label="Nom / Raison sociale" name="name" :value="$client->name" required />
            <x-input label="E-mail" name="email" type="email" :value="$client->email" />
            <x-input label="Téléphone" name="phone" :value="$client->phone" />
            <x-input class="sm:col-span-2" label="Adresse" name="address" :value="$client->address" />
            <x-input label="Ville" name="city" :value="$client->city" />
            <x-input label="Pays" name="country" :value="$client->country ?? 'Sénégal'" />
            <x-input class="sm:col-span-2" label="NINEA / N° fiscal" name="tax_number" :value="$client->tax_number" />
            <div class="sm:col-span-2">
                <label class="label" for="notes">Notes internes</label>
                <textarea id="notes" name="notes" rows="3" class="input">{{ old('notes', $client->notes) }}</textarea>
                @error('notes')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex items-center justify-between border-t border-slate-100 pt-5 dark:border-white/5">
            @if($editing)
                @can('clients.manage')
                    <button form="delete-client" class="btn-danger" onclick="return confirm('Supprimer ce client ? Son historique est conservé.')"><x-icon name="trash" class="size-4" /> Supprimer</button>
                @endcan
            @else <span></span> @endif
            <div class="flex gap-2">
                <a href="{{ route('clients.index') }}" class="btn-secondary">Annuler</a>
                <button class="btn-primary"><x-icon name="check" class="size-4" /> Enregistrer</button>
            </div>
        </div>
    </form>
    @if($editing)
        <form id="delete-client" method="POST" action="{{ route('clients.destroy', $client) }}" class="hidden">@csrf @method('DELETE')</form>
    @endif
</x-layouts.app>
