<x-layouts.app title="Clients">
    <x-page-header title="Clients" subtitle="Gérez votre portefeuille clients.">
        @can('clients.manage')
            <a href="{{ route('clients.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Nouveau client</a>
        @endcan
    </x-page-header>

    <div class="card overflow-hidden">
        <form method="GET" class="flex gap-3 border-b border-slate-100 p-4 dark:border-white/5">
            <div class="relative flex-1">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
                <input name="search" value="{{ request('search') }}" placeholder="Rechercher par nom, e-mail ou téléphone…" class="input !pl-10">
            </div>
            <button class="btn-secondary">Filtrer</button>
        </form>

        @if($clients->isEmpty())
            <x-empty icon="users" title="Aucun client trouvé" text="Ajoutez votre premier client pour commencer à facturer.">
                @can('clients.manage')<a href="{{ route('clients.create') }}" class="btn-primary">Ajouter un client</a>@endcan
            </x-empty>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50/60 dark:bg-white/[0.02]">
                        <tr><th class="th">Client</th><th class="th">Contact</th><th class="th">Ville</th><th class="th text-center">Documents</th><th class="th"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        @foreach($clients as $client)
                            <tr class="tr-hover">
                                <td class="td">
                                    <a href="{{ route('clients.show', $client) }}" class="flex items-center gap-3">
                                        <span class="grid size-10 shrink-0 place-items-center rounded-md bg-brand-50 text-sm font-semibold text-brand-700">{{ mb_strtoupper(mb_substr($client->name, 0, 2)) }}</span>
                                        <span class="font-semibold text-slate-900 dark:text-white">{{ $client->name }}</span>
                                    </a>
                                </td>
                                <td class="td text-slate-500">{{ $client->email }}<br><span class="text-xs">{{ $client->phone }}</span></td>
                                <td class="td text-slate-500">{{ $client->city }}</td>
                                <td class="td text-center"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold dark:bg-white/10">{{ $client->documents_count }}</span></td>
                                <td class="td text-right">
                                    <a href="{{ route('clients.show', $client) }}" class="btn-ghost !px-2.5 !py-1.5"><x-icon name="eye" class="size-4" /></a>
                                    @can('clients.manage')<a href="{{ route('clients.edit', $client) }}" class="btn-ghost !px-2.5 !py-1.5"><x-icon name="pencil" class="size-4" /></a>@endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($clients->hasPages())<div class="border-t border-slate-100 p-4 dark:border-white/5">{{ $clients->links() }}</div>@endif
        @endif
    </div>
</x-layouts.app>
