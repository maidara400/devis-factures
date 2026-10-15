@php
    $editing = $document->exists;
    $oldLines = old('lines');
    $initial = $oldLines
        ? collect($oldLines)->map(fn ($l) => [...$l, 'key' => (string) \Illuminate\Support\Str::uuid()])->values()->all()
        : $lines;
    $heading = $editing ? 'Modifier '.$document->number : ($document->type->value === 'invoice' ? 'Nouvelle facture' : 'Nouveau devis');
@endphp
<x-layouts.app :title="$heading">
    <x-page-header :title="$heading"
                   :back="$editing ? route('documents.show', $document) : route('documents.index')"
                   subtitle="Le numéro est attribué automatiquement à l'enregistrement." />

    @if($errors->any())
        <div class="mb-6 rounded-md border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300">
            <p class="font-semibold">Veuillez corriger les erreurs suivantes :</p>
            <ul class="mt-1 list-inside list-disc">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $editing ? route('documents.update', $document) : route('documents.store') }}"
          x-data="documentForm(@js($initial), @js($articles))" class="grid items-start gap-6 lg:grid-cols-3">
        @csrf
        @if($editing) @method('PUT') @else <input type="hidden" name="type" value="{{ $document->type->value }}"> @endif

        <div class="space-y-6 lg:col-span-2">
            {{-- Lignes --}}
            <div class="card p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="font-semibold text-slate-900 dark:text-white">Lignes</h2>
                    <button type="button" @click="add()" class="btn-secondary !py-2"><x-icon name="plus" class="size-4" /> Ajouter une ligne</button>
                </div>

                <div class="space-y-4">
                    <template x-for="(line, i) in lines" :key="line.key">
                        <div class="relative rounded-md border border-slate-200 bg-slate-50/60 p-4 dark:border-white/10 dark:bg-white/[0.02]">
                            <div class="grid gap-3 sm:grid-cols-12">
                                <div class="sm:col-span-12">
                                    <label class="label">Article (optionnel)</label>
                                    <select :name="`lines[${i}][article_id]`" x-model="line.article_id" @change="pick(line)" class="input">
                                        <option value="">— Ligne libre —</option>
                                        <template x-for="a in articles" :key="a.id">
                                            <option :value="a.id" x-text="`${a.reference} · ${a.name}`" :selected="String(a.id) === String(line.article_id)"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="sm:col-span-12">
                                    <label class="label">Désignation</label>
                                    <input type="text" :name="`lines[${i}][description]`" x-model="line.description" class="input" required>
                                </div>
                                <div class="sm:col-span-3">
                                    <label class="label">Quantité</label>
                                    <input type="number" step="0.01" min="0.01" :name="`lines[${i}][quantity]`" x-model.number="line.quantity" class="input" required>
                                </div>
                                <div class="sm:col-span-4">
                                    <label class="label">Prix HT</label>
                                    <input type="number" step="0.01" min="0" :name="`lines[${i}][unit_price]`" x-model.number="line.unit_price" class="input" required>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="label">TVA %</label>
                                    <input type="number" step="0.01" min="0" max="100" :name="`lines[${i}][tax_rate]`" x-model.number="line.tax_rate" class="input" required>
                                </div>
                                <div class="flex items-end justify-between gap-2 sm:col-span-3">
                                    <div class="text-right">
                                        <p class="label !mb-0">Total HT</p>
                                        <p class="text-sm font-semibold tabular-nums" x-text="money(lineSubtotal(line))"></p>
                                    </div>
                                    <button type="button" @click="remove(i)" :disabled="lines.length === 1" class="btn-danger !p-2.5" aria-label="Supprimer la ligne"><x-icon name="trash" class="size-4" /></button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="card p-6">
                <label class="label" for="notes">Notes (affichées sur le document)</label>
                <textarea id="notes" name="notes" rows="3" class="input" placeholder="Conditions particulières, remarques…">{{ old('notes', $document->notes) }}</textarea>
            </div>
        </div>

        {{-- Colonne droite --}}
        <div class="space-y-6 lg:sticky lg:top-24">
            <div class="card space-y-4 p-6">
                <h2 class="font-semibold text-slate-900 dark:text-white">Informations</h2>
                <div>
                    <label class="label" for="client_id">Client <span class="text-rose-500">*</span></label>
                    <select id="client_id" name="client_id" class="input" required>
                        <option value="">Sélectionner…</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}" @selected((string) old('client_id', $document->client_id) === (string) $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <x-input label="Date d'émission" name="issue_date" type="date" :value="$document->issue_date?->toDateString()" required />
                <x-input :label="$document->type->value === 'quote' ? 'Valable jusqu\'au' : 'Échéance de paiement'" name="due_date" type="date" :value="$document->due_date?->toDateString()" />
            </div>

            <div class="card p-6">
                <div class="space-y-2.5">
                    <div class="flex justify-between text-sm text-slate-600"><span>Total HT</span><span class="tabular-nums" x-text="money(subtotal)"></span></div>
                    <div class="flex justify-between text-sm text-slate-600"><span>TVA</span><span class="tabular-nums" x-text="money(tax)"></span></div>
                    <div class="flex items-baseline justify-between border-t border-slate-200 pt-3">
                        <span class="text-sm font-semibold text-slate-900">Total TTC</span>
                        <span class="text-xl font-semibold tabular-nums text-slate-900" x-text="money(total)"></span>
                    </div>
                </div>
            </div>

            <button class="btn-primary w-full !py-3"><x-icon name="check" class="size-4" /> {{ $editing ? 'Enregistrer les modifications' : 'Créer le brouillon' }}</button>
        </div>
    </form>
</x-layouts.app>
