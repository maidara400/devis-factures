<x-layouts.app title="Utilisateurs">
    <x-page-header title="Utilisateurs" subtitle="Gérez les accès et les rôles de votre équipe.">
        <a href="{{ route('users.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Nouvel utilisateur</a>
    </x-page-header>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50/60 dark:bg-white/[0.02]"><tr><th class="th">Utilisateur</th><th class="th">Rôle</th><th class="th">Statut</th><th class="th"></th></tr></thead>
                <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                    @foreach($users as $u)
                        <tr class="tr-hover">
                            <td class="td">
                                <div class="flex items-center gap-3">
                                    <span class="grid size-10 place-items-center rounded-full bg-gradient-to-br from-brand-400 to-fuchsia-500 text-sm font-bold text-white">{{ mb_strtoupper(mb_substr($u->name, 0, 1)) }}</span>
                                    <div><p class="font-semibold text-slate-900 dark:text-white">{{ $u->name }}@if($u->is(auth()->user())) <span class="text-xs font-normal text-slate-400">(vous)</span>@endif</p><p class="text-xs text-slate-500">{{ $u->email }}</p></div>
                                </div>
                            </td>
                            <td class="td"><span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">{{ $u->role->label() }}</span></td>
                            <td class="td"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $u->is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-slate-100 text-slate-500 dark:bg-white/10' }}">{{ $u->is_active ? 'Actif' : 'Désactivé' }}</span></td>
                            <td class="td text-right"><a href="{{ route('users.edit', $u) }}" class="btn-ghost !px-2.5 !py-1.5"><x-icon name="pencil" class="size-4" /></a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($users->hasPages())<div class="border-t border-slate-100 p-4 dark:border-white/5">{{ $users->links() }}</div>@endif
    </div>
</x-layouts.app>
