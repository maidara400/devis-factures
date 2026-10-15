@php use App\Support\Money; @endphp
<x-layouts.app title="Tableau de bord">
    @php
        $max = max(1, collect($chart)->max('invoiced'));
    @endphp

    <x-page-header :title="'Bonjour, '.explode(' ', auth()->user()->name)[0].' 👋'" subtitle="Voici l'état de votre activité commerciale.">
        @can('documents.export')
            <a href="{{ route('documents.export') }}" class="btn-secondary"><x-icon name="download" class="size-4" /> Export CSV</a>
        @endcan
    </x-page-header>

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Encaissé ce mois" :value="Money::format($stats['paid_month'])" icon="banknotes" tone="emerald" hint="Factures payées" />
        <x-stat label="En attente" :value="Money::format($stats['pending'])" icon="clock" tone="brand" hint="Factures envoyées" />
        <x-stat label="Factures en retard" :value="$stats['overdue']" icon="exclamation" tone="rose" hint="Échéance dépassée" />
        <x-stat label="Brouillons" :value="$stats['drafts']" icon="pencil" tone="amber" :hint="$stats['open_quotes'].' devis en attente de réponse'" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        {{-- Chart --}}
        <div class="card p-6 lg:col-span-2">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-slate-900 dark:text-white">Facturation des 6 derniers mois</h2>
                    <p class="text-xs text-slate-500">Montant facturé vs encaissé</p>
                </div>
                <div class="flex items-center gap-4 text-xs text-slate-500">
                    <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-brand-300"></span>Facturé</span>
                    <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-emerald-500"></span>Encaissé</span>
                </div>
            </div>
            <div class="mt-8 flex h-56 items-end gap-3 sm:gap-6">
                @foreach($chart as $m)
                    <div class="group flex h-full flex-1 flex-col items-center gap-2">
                        <div class="relative flex w-full flex-1 items-end justify-center gap-1.5">
                            <div class="w-full max-w-7 rounded-t-lg bg-gradient-to-t from-brand-300 to-brand-200 transition-all duration-500 group-hover:from-brand-400 dark:from-brand-500/60 dark:to-brand-400/40"
                                 style="height: {{ max(2, $m['invoiced'] / $max * 100) }}%"></div>
                            <div class="w-full max-w-7 rounded-t-lg bg-gradient-to-t from-emerald-600 to-emerald-400 transition-all duration-500"
                                 style="height: {{ max(2, $m['paid'] / $max * 100) }}%"></div>
                            <div class="pointer-events-none absolute -top-2 z-10 hidden -translate-y-full rounded-lg bg-slate-900 px-2.5 py-1.5 text-xs whitespace-nowrap text-white shadow-lg group-hover:block">
                                {{ Money::format($m['invoiced']) }}<br><span class="text-emerald-300">{{ Money::format($m['paid']) }}</span>
                            </div>
                        </div>
                        <span class="text-xs font-medium text-slate-500 capitalize">{{ $m['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Quick links --}}
        <div class="card flex flex-col p-6">
            <h2 class="font-semibold text-slate-900 dark:text-white">Raccourcis</h2>
            <div class="mt-4 space-y-2.5">
                @can('documents.manage')
                    <a href="{{ route('documents.create', ['type' => 'quote']) }}" class="group flex items-center gap-3 rounded-xl border border-slate-200 p-3.5 transition hover:border-brand-300 hover:bg-brand-50/60 dark:border-white/10 dark:hover:bg-white/5">
                        <span class="grid size-10 place-items-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/15"><x-icon name="document" /></span>
                        <span class="flex-1 text-sm font-semibold">Nouveau devis</span>
                        <x-icon name="arrow-right" class="size-4 text-slate-400 transition group-hover:translate-x-1" />
                    </a>
                    <a href="{{ route('documents.create', ['type' => 'invoice']) }}" class="group flex items-center gap-3 rounded-xl border border-slate-200 p-3.5 transition hover:border-brand-300 hover:bg-brand-50/60 dark:border-white/10 dark:hover:bg-white/5">
                        <span class="grid size-10 place-items-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15"><x-icon name="receipt" /></span>
                        <span class="flex-1 text-sm font-semibold">Nouvelle facture</span>
                        <x-icon name="arrow-right" class="size-4 text-slate-400 transition group-hover:translate-x-1" />
                    </a>
                @endcan
                @can('clients.manage')
                    <a href="{{ route('clients.create') }}" class="group flex items-center gap-3 rounded-xl border border-slate-200 p-3.5 transition hover:border-brand-300 hover:bg-brand-50/60 dark:border-white/10 dark:hover:bg-white/5">
                        <span class="grid size-10 place-items-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-500/15"><x-icon name="users" /></span>
                        <span class="flex-1 text-sm font-semibold">Ajouter un client</span>
                        <x-icon name="arrow-right" class="size-4 text-slate-400 transition group-hover:translate-x-1" />
                    </a>
                @endcan
            </div>
            <p class="mt-auto pt-6 text-xs text-slate-500">{{ $stats['clients'] }} clients enregistrés</p>
        </div>
    </div>

    {{-- Recent --}}
    <div class="card mt-6 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4">
            <h2 class="font-semibold text-slate-900 dark:text-white">Activité récente</h2>
            @can('documents.view')<a href="{{ route('documents.index') }}" class="text-sm font-semibold text-brand-600 hover:underline dark:text-brand-300">Tout voir</a>@endcan
        </div>
        @if($recent->isEmpty())
            <x-empty icon="receipt" title="Aucun document pour le moment" text="Créez votre premier devis ou votre première facture." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="border-y border-slate-100 bg-slate-50/60 dark:border-white/5 dark:bg-white/[0.02]">
                        <tr><th class="th">Numéro</th><th class="th">Client</th><th class="th">Statut</th><th class="th text-right">Total TTC</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        @foreach($recent as $d)
                            <tr class="tr-hover">
                                <td class="td"><a href="{{ route('documents.show', $d) }}" class="font-semibold text-brand-700 hover:underline dark:text-brand-300">{{ $d->number }}</a></td>
                                <td class="td">{{ $d->client->name }}</td>
                                <td class="td"><x-status-badge :document="$d" /></td>
                                <td class="td text-right font-semibold tabular-nums">{{ Money::format($d->total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-layouts.app>

