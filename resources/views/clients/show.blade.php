@php use App\Support\Money; @endphp
<x-layouts.app :title="$client->name">
    <x-page-header :title="$client->name" :subtitle="collect([$client->city, $client->country])->filter()->implode(', ')" :back="route('clients.index')">
        @can('clients.manage')<a href="{{ route('clients.edit', $client) }}" class="btn-secondary"><x-icon name="pencil" class="size-4" /> Modifier</a>@endcan
        @can('documents.manage')<a href="{{ route('documents.create', ['client_id' => $client->id]) }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Nouveau document</a>@endcan
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card space-y-4 p-6">
            <h2 class="font-semibold text-slate-900 dark:text-white">Coordonnées</h2>
            @foreach(['E-mail' => $client->email, 'Téléphone' => $client->phone, 'Adresse' => $client->address, 'NINEA' => $client->tax_number] as $label => $value)
                <div><p class="label !mb-0.5">{{ $label }}</p><p class="text-sm">{{ $value ?: '—' }}</p></div>
            @endforeach
            @if($client->notes)<div><p class="label !mb-0.5">Notes</p><p class="text-sm whitespace-pre-line text-slate-600 dark:text-slate-400">{{ $client->notes }}</p></div>@endif
        </div>

        <div class="card overflow-hidden lg:col-span-2">
            <h2 class="px-6 py-4 font-semibold text-slate-900 dark:text-white">Historique ({{ $documents->count() }})</h2>
            @if($documents->isEmpty())
                <x-empty icon="receipt" title="Aucun document" text="Aucun devis ou facture pour ce client." />
            @else
                <table class="w-full">
                    <thead class="bg-slate-50/60 dark:bg-white/[0.02]"><tr><th class="th">Numéro</th><th class="th">Date</th><th class="th">Statut</th><th class="th text-right">Total</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        @foreach($documents as $d)
                            <tr class="tr-hover">
                                <td class="td"><a href="{{ route('documents.show', $d) }}" class="font-semibold text-brand-700 hover:underline dark:text-brand-300">{{ $d->number }}</a></td>
                                <td class="td text-slate-500">{{ $d->issue_date->format('d/m/Y') }}</td>
                                <td class="td"><x-status-badge :document="$d" /></td>
                                <td class="td text-right font-semibold tabular-nums">{{ Money::format($d->total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-layouts.app>

