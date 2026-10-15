@php($editing = $article->exists)
<x-layouts.app :title="$editing ? 'Modifier l\'article' : 'Nouvel article'">
    <x-page-header :title="$editing ? 'Modifier '.$article->name : 'Nouvel article'" :back="route('articles.index')" />

    <form method="POST" action="{{ $editing ? route('articles.update', $article) : route('articles.store') }}" class="card max-w-3xl space-y-5 p-6 sm:p-8">
        @csrf
        @if($editing) @method('PUT') @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <x-input label="Référence" name="reference" :value="$article->reference" required placeholder="ART-001" />
            <x-input label="Unité" name="unit" :value="$article->unit" placeholder="unité, heure, jour…" />
            <x-input class="sm:col-span-2" label="Désignation" name="name" :value="$article->name" required />
            <div class="sm:col-span-2">
                <label class="label" for="description">Description</label>
                <textarea id="description" name="description" rows="3" class="input">{{ old('description', $article->description) }}</textarea>
            </div>
            <x-input label="Prix unitaire HT (FCFA)" name="unit_price" type="number" step="0.01" min="0" :value="$article->unit_price" required />
            <x-input label="TVA (%)" name="tax_rate" type="number" step="0.01" min="0" max="100" :value="$article->tax_rate" required />
            <label class="flex cursor-pointer items-center gap-2.5 text-sm sm:col-span-2">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $article->is_active)) class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                Article actif (proposé dans les documents)
            </label>
        </div>

        <div class="flex items-center justify-between border-t border-slate-100 pt-5 dark:border-white/5">
            @if($editing)
                <button form="delete-article" class="btn-danger" onclick="return confirm('Supprimer cet article ?')"><x-icon name="trash" class="size-4" /> Supprimer</button>
            @else <span></span> @endif
            <div class="flex gap-2">
                <a href="{{ route('articles.index') }}" class="btn-secondary">Annuler</a>
                <button class="btn-primary"><x-icon name="check" class="size-4" /> Enregistrer</button>
            </div>
        </div>
    </form>
    @if($editing)
        <form id="delete-article" method="POST" action="{{ route('articles.destroy', $article) }}" class="hidden">@csrf @method('DELETE')</form>
    @endif
</x-layouts.app>
