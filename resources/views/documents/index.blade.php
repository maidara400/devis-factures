@php use App\Support\Money; @endphp
<x-layouts.app title="Devis & Factures">
    @php
        $tabs = ['' => 'Tous', 'quote' => 'Devis', 'invoice' => 'Factures'];
        $qs = fn (array $extra) => route('documents.index', array_filter([...request()->except('page'), ...$extra], fn ($v) => $v !== null && $v !== ''));
    @endphp

    <x-page-header title="Devis & Factures" subtitle="Suivez vos documents commerciaux du brouillon au paiement.">
        @can('documents.export')
            <a href="{{ route('documents.export', request()->query()) }}" class="btn-secondary"><x-icon name="download" class="size-4" /> Export CSV</a>
        @endcan
        @can('documents.manage')
            <a href="{{ route('documents.create', ['type' => 'quote']) }}" class="btn-secondary"><x-icon name="plus" class="size-4" /> Devis</a>
            <a href="{{ route('documents.create', ['type' => 'invoice']) }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Facture</a>
        @endcan
    </x-page-header>

    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 p-4 dark:border-white/5">
            <div class="flex rounded-xl bg-slate-100 p-1 dark:bg-white/5">
                @foreach($tabs as $value => $label)
                    <a href="{{ $qs(['type' => $value]) }}"
                       class="rounded-lg px-4 py-1.5 text-sm font-semibold transition {{ ($filters['type'] ?? '') === (string) $value ? 'bg-white text-brand-700 shadow-sm dark:bg-slate-800 dark:text-brand-300' : 'text-slate-500 hover:text-slate-800 dark:hover:text-white' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
            <form method="GET" class="flex flex-1 flex-wrap gap-3">
                @if(! empty($filters['type']))<input type="hidden" name="type" value="{{ $filters['type'] }}">@endif
                <div class="relative min-w-52 flex-1">
                    <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
                    <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="N° ou client…" class="input !pl-10">
                </div>
                <select name="status" class="input !w-auto" onchange="this.form.submit()">
                    <option value="">Tous statuts</option>
                    @foreach(\App\Enums\DocumentStatus::cases() as $s)
                        <option value="{{ $s->value }}" @selected(($filters['status'] ?? '') === $s->value)>{{ $s->label() }}</option>
                    @endforeach
                </select>
                <button class="btn-secondary">Filtrer</button>
            </form>
        </div>

        @if($documents->isEmpty())
            <x-empty icon="receipt" title="Aucun document" text="Aucun résultat pour ces critères.">
                @can('documents.manage')<a href="{{ route('documents.create') }}" class="btn-primary">Créer un document</a>@endcan
            </x-empty>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50/60 dark:bg-white/[0.02]">
                        <tr><th class="th">Numéro</th><th class="th">Client</th><th class="th">Émission</th><th class="th">Échéance</th><th class="th">Statut</th><th class="th text-right">Total TTC</th><th class="th"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        @foreach($documents as $d)
                            <tr class="tr-hover">
                                <td class="td">
                                    <a href="{{ route('documents.show', $d) }}" class="flex items-center gap-3">
                                        <span class="grid size-9 place-items-center rounded-lg {{ $d->type->value === 'quote' ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/15' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15' }}"><x-icon :name="$d->type->value === 'quote' ? 'document' : 'receipt'" class="size-4.5" /></span>
                                        <span><span class="block font-semibold text-slate-900 dark:text-white">{{ $d->number }}</span><span class="text-xs text-slate-500">{{ $d->type->label() }}</span></span>
                                    </a>
                                </td>
                                <td class="td">{{ $d->client->name }}</td>
                                <td class="td text-slate-500">{{ $d->issue_date->format('d/m/Y') }}</td>
                                <td class="td text-slate-500">{{ $d->due_date?->format('d/m/Y') ?? '—' }}</td>
                                <td class="td"><x-status-badge :document="$d" /></td>
                                <td class="td text-right font-semibold tabular-nums">{{ Money::format($d->total) }}</td>
                                <td class="td text-right whitespace-nowrap">
                                    <a href="{{ route('documents.pdf', $d) }}" target="_blank" class="btn-ghost !px-2.5 !py-1.5" title="PDF"><x-icon name="download" class="size-4" /></a>
                                    <a href="{{ route('documents.show', $d) }}" class="btn-ghost !px-2.5 !py-1.5" title="Ouvrir"><x-icon name="eye" class="size-4" /></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($documents->hasPages())<div class="border-t border-slate-100 p-4 dark:border-white/5">{{ $documents->links() }}</div>@endif
        @endif
    </div>
</x-layouts.app>

