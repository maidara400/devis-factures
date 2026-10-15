@php use App\Support\Money; @endphp
<x-layouts.app title="Articles">
    <x-page-header title="Articles" subtitle="Catalogue de produits et services réutilisables dans vos documents.">
        @can('articles.manage')
            <a href="{{ route('articles.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Nouvel article</a>
        @endcan
    </x-page-header>

    <div class="card overflow-hidden">
        <form method="GET" class="flex gap-3 border-b border-slate-100 p-4 dark:border-white/5">
            <div class="relative flex-1">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
                <input name="search" value="{{ request('search') }}" placeholder="Rechercher par nom ou référence…" class="input !pl-10">
            </div>
            <button class="btn-secondary">Filtrer</button>
        </form>

        @if($articles->isEmpty())
            <x-empty icon="cube" title="Aucun article" text="Créez des articles pour remplir vos devis en un clic.">
                @can('articles.manage')<a href="{{ route('articles.create') }}" class="btn-primary">Créer un article</a>@endcan
            </x-empty>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50/60 dark:bg-white/[0.02]"><tr><th class="th">Référence</th><th class="th">Désignation</th><th class="th text-right">Prix HT</th><th class="th text-center">TVA</th><th class="th">Statut</th><th class="th"></th></tr></thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        @foreach($articles as $a)
                            <tr class="tr-hover">
                                <td class="td"><code class="rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold dark:bg-white/10">{{ $a->reference }}</code></td>
                                <td class="td"><p class="font-semibold text-slate-900 dark:text-white">{{ $a->name }}</p><p class="text-xs text-slate-500">{{ $a->description }}</p></td>
                                <td class="td text-right font-semibold tabular-nums">{{ Money::format($a->unit_price) }}<span class="block text-xs font-normal text-slate-500">/ {{ $a->unit }}</span></td>
                                <td class="td text-center text-slate-500">{{ rtrim(rtrim($a->tax_rate, '0'), '.') }} %</td>
                                <td class="td">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $a->is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-slate-100 text-slate-500 dark:bg-white/10' }}">{{ $a->is_active ? 'Actif' : 'Inactif' }}</span>
                                </td>
                                <td class="td text-right">@can('articles.manage')<a href="{{ route('articles.edit', $a) }}" class="btn-ghost !px-2.5 !py-1.5"><x-icon name="pencil" class="size-4" /></a>@endcan</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($articles->hasPages())<div class="border-t border-slate-100 p-4 dark:border-white/5">{{ $articles->links() }}</div>@endif
        @endif
    </div>
</x-layouts.app>

