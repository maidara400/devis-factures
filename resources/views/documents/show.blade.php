@php
    use App\Enums\DocumentStatus;
    use App\Enums\DocumentType;
    use App\Support\Money;
    $isQuote = $document->type === DocumentType::Quote;
    $steps = [DocumentStatus::Draft, DocumentStatus::Sent, DocumentStatus::Paid];
    $currentIndex = array_search($document->status, $steps, true);
@endphp
<x-layouts.app :title="$document->number">
    <x-page-header :title="$document->number" :back="route('documents.index')"
                   :subtitle="$document->type->label().' pour '.$document->client->name">
        <x-status-badge :document="$document" />
    </x-page-header>

    <div class="grid items-start gap-6 lg:grid-cols-3">
        {{-- Aperçu du document --}}
        <div class="card overflow-hidden lg:col-span-2">
            <div class="relative overflow-hidden bg-gradient-to-br from-brand-600 to-violet-700 px-8 py-7 text-white">
                <div class="absolute -top-16 -right-10 size-56 rounded-full bg-white/10 blur-2xl"></div>
                <div class="relative flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-lg font-bold">{{ config('company.name') }}</p>
                        <p class="mt-1 text-xs leading-relaxed text-brand-100">{{ config('company.address') }}<br>{{ config('company.email') }} · {{ config('company.phone') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold tracking-widest uppercase">{{ $document->type->label() }}</p>
                        <p class="text-sm text-brand-100">N° {{ $document->number }}</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 p-8 sm:grid-cols-2">
                <div>
                    <p class="label">Client</p>
                    <a href="{{ route('clients.show', $document->client) }}" class="text-base font-bold text-slate-900 hover:underline dark:text-white">{{ $document->client->name }}</a>
                    <p class="mt-1 text-sm leading-relaxed text-slate-500">
                        {{ $document->client->address }}@if($document->client->address)<br>@endif
                        {{ $document->client->city }}@if($document->client->city), @endif{{ $document->client->country }}<br>
                        {{ $document->client->email }}
                    </p>
                </div>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm sm:justify-self-end">
                    <dt class="text-slate-500">Émission</dt><dd class="text-right font-semibold">{{ $document->issue_date->format('d/m/Y') }}</dd>
                    <dt class="text-slate-500">{{ $isQuote ? 'Validité' : 'Échéance' }}</dt><dd class="text-right font-semibold">{{ $document->due_date?->format('d/m/Y') ?? '—' }}</dd>
                    <dt class="text-slate-500">Créé par</dt><dd class="text-right font-semibold">{{ $document->user->name }}</dd>
                    @if($document->sourceQuote)
                        <dt class="text-slate-500">Issu du devis</dt><dd class="text-right font-semibold"><a class="text-brand-600 hover:underline" href="{{ route('documents.show', $document->sourceQuote) }}">{{ $document->sourceQuote->number }}</a></dd>
                    @endif
                    @if($document->invoice)
                        <dt class="text-slate-500">Facture liée</dt><dd class="text-right font-semibold"><a class="text-brand-600 hover:underline" href="{{ route('documents.show', $document->invoice) }}">{{ $document->invoice->number }}</a></dd>
                    @endif
                </dl>
            </div>

            <div class="overflow-x-auto px-8">
                <table class="w-full">
                    <thead><tr class="border-y border-slate-100 dark:border-white/5"><th class="th !px-0">Désignation</th><th class="th text-right">Qté</th><th class="th text-right">P.U. HT</th><th class="th text-right">TVA</th><th class="th !pr-0 text-right">Total HT</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        @foreach($document->lines as $line)
                            <tr>
                                <td class="td !px-0 font-medium">{{ $line->description }}</td>
                                <td class="td text-right tabular-nums">{{ rtrim(rtrim($line->quantity, '0'), '.') }}</td>
                                <td class="td text-right tabular-nums">{{ Money::format($line->unit_price) }}</td>
                                <td class="td text-right text-slate-500">{{ rtrim(rtrim($line->tax_rate, '0'), '.') }} %</td>
                                <td class="td !pr-0 text-right font-semibold tabular-nums">{{ Money::format($line->subtotal) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end p-8">
                <dl class="w-full max-w-xs space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Total HT</dt><dd class="font-semibold tabular-nums">{{ Money::format($document->subtotal) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">TVA</dt><dd class="font-semibold tabular-nums">{{ Money::format($document->tax_total) }}</dd></div>
                    <div class="flex items-baseline justify-between rounded-xl bg-brand-50 px-4 py-3 dark:bg-brand-500/10">
                        <dt class="font-semibold text-brand-800 dark:text-brand-200">Total TTC</dt>
                        <dd class="text-xl font-bold text-brand-700 tabular-nums dark:text-brand-200">{{ Money::format($document->total) }}</dd>
                    </div>
                </dl>
            </div>

            @if($document->notes)
                <div class="border-t border-slate-100 px-8 py-5 text-sm whitespace-pre-line text-slate-600 dark:border-white/5 dark:text-slate-400"><p class="label">Notes</p>{{ $document->notes }}</div>
            @endif
        </div>

        {{-- Panneau d'actions --}}
        <div class="space-y-6 lg:sticky lg:top-24">
            <div class="card p-6">
                <h2 class="mb-5 font-semibold text-slate-900 dark:text-white">Progression</h2>
                <ol class="relative space-y-5">
                    @foreach($steps as $i => $step)
                        @php($done = $i <= $currentIndex)
                        <li class="relative flex items-center gap-3">
                            @if(! $loop->last)<span class="absolute top-8 left-4 h-5 w-px {{ $i < $currentIndex ? 'bg-brand-500' : 'bg-slate-200 dark:bg-white/10' }}"></span>@endif
                            <span class="grid size-8 place-items-center rounded-full text-xs font-bold {{ $done ? 'bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-glow' : 'bg-slate-100 text-slate-400 dark:bg-white/5' }}">
                                @if($done)<x-icon name="check" class="size-4" />@else {{ $i + 1 }} @endif
                            </span>
                            <span class="text-sm {{ $done ? 'font-semibold text-slate-900 dark:text-white' : 'text-slate-400' }}">
                                {{ $step->label($document->type) }}
                                @if($step === DocumentStatus::Sent && $document->sent_at)<span class="block text-xs font-normal text-slate-500">{{ $document->sent_at->format('d/m/Y H:i') }}</span>@endif
                                @if($step === DocumentStatus::Paid && $document->paid_at)<span class="block text-xs font-normal text-slate-500">{{ $document->paid_at->format('d/m/Y H:i') }}</span>@endif
                            </span>
                        </li>
                    @endforeach
                </ol>
            </div>

            <div class="card space-y-2.5 p-6">
                <h2 class="mb-3 font-semibold text-slate-900 dark:text-white">Actions</h2>

                @can('documents.status')
                    @if($document->status === DocumentStatus::Draft)
                        <form method="POST" action="{{ route('documents.status', $document) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="sent">
                            <button class="btn-primary w-full"><x-icon name="send" class="size-4" /> Marquer comme envoyé</button></form>
                    @elseif($document->status === DocumentStatus::Sent)
                        <form method="POST" action="{{ route('documents.status', $document) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="paid">
                            <button class="btn-primary w-full !from-emerald-500 !to-emerald-700"><x-icon name="check" class="size-4" /> Marquer {{ $isQuote ? 'accepté' : 'payé' }}</button></form>
                        <form method="POST" action="{{ route('documents.status', $document) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="draft">
                            <button class="btn-secondary w-full">Repasser en brouillon</button></form>
                    @endif
                @endcan

                @can('documents.manage')
                    @if($document->client->email)
                        <form method="POST" action="{{ route('documents.send', $document) }}" onsubmit="return confirm('Envoyer ce document à {{ $document->client->email }} ?')">@csrf
                            <button class="btn-secondary w-full"><x-icon name="send" class="size-4" /> Envoyer par e-mail</button></form>
                    @endif
                    @if($isQuote && ! $document->isDraft() && ! $document->invoice)
                        <form method="POST" action="{{ route('documents.convert', $document) }}">@csrf
                            <button class="btn-secondary w-full"><x-icon name="convert" class="size-4" /> Convertir en facture</button></form>
                    @endif
                    @if($document->isDraft())
                        <a href="{{ route('documents.edit', $document) }}" class="btn-secondary w-full"><x-icon name="pencil" class="size-4" /> Modifier</a>
                    @endif
                @endcan

                <a href="{{ route('documents.pdf', $document) }}" target="_blank" class="btn-secondary w-full"><x-icon name="eye" class="size-4" /> Aperçu PDF</a>
                <a href="{{ route('documents.pdf', [$document, 'download' => 1]) }}" class="btn-secondary w-full"><x-icon name="download" class="size-4" /> Télécharger le PDF</a>

                @can('documents.delete')
                    @if($document->isDraft())
                        <form method="POST" action="{{ route('documents.destroy', $document) }}" onsubmit="return confirm('Supprimer ce brouillon ?')">@csrf @method('DELETE')
                            <button class="btn-danger w-full"><x-icon name="trash" class="size-4" /> Supprimer</button></form>
                    @endif
                @endcan
            </div>
        </div>
    </div>
</x-layouts.app>
