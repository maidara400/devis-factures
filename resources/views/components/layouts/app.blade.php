@props(['title' => null])
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('company.name') }}</title>
    <script>
        try { if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark'); } catch (e) {}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen" x-data="{ nav: false }">
@php
    $nav = [
        ['route' => 'dashboard', 'match' => 'dashboard', 'label' => 'Tableau de bord', 'icon' => 'home', 'can' => null],
        ['route' => 'documents.index', 'match' => 'documents.*', 'label' => 'Devis & Factures', 'icon' => 'receipt', 'can' => 'documents.view'],
        ['route' => 'clients.index', 'match' => 'clients.*', 'label' => 'Clients', 'icon' => 'users', 'can' => 'clients.view'],
        ['route' => 'articles.index', 'match' => 'articles.*', 'label' => 'Articles', 'icon' => 'cube', 'can' => 'articles.view'],
        ['route' => 'users.index', 'match' => 'users.*', 'label' => 'Utilisateurs', 'icon' => 'shield', 'can' => 'users.manage'],
    ];
@endphp

{{-- Overlay mobile --}}
<div x-show="nav" x-cloak x-transition.opacity @click="nav = false" class="fixed inset-0 z-30 bg-slate-900/50 backdrop-blur-sm lg:hidden"></div>

{{-- Sidebar --}}
<aside :class="nav ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
       class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col bg-slate-900 text-slate-300 transition-transform duration-300 lg:z-20">
    <div class="absolute inset-0 -z-10 bg-[radial-gradient(at_20%_0%,rgb(99_102_241/0.35),transparent_55%)]"></div>
    <div class="flex h-20 items-center gap-3 px-6">
        <div class="grid size-10 place-items-center rounded-xl bg-gradient-to-br from-brand-400 to-brand-700 text-white shadow-glow">
            <x-icon name="sparkles" class="size-5" />
        </div>
        <div class="leading-tight">
            <div class="text-[15px] font-bold tracking-tight text-white">Devisio</div>
            <div class="text-[11px] text-slate-400">Devis &amp; Factures</div>
        </div>
    </div>

    <nav class="flex-1 space-y-1 px-4 py-2">
        @foreach($nav as $item)
            @if(! $item['can'] || auth()->user()->can($item['can']))
                @php($active = request()->routeIs($item['match']))
                <a href="{{ route($item['route']) }}"
                   class="group flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition
                          {{ $active ? 'bg-white/10 text-white shadow-inner ring-1 ring-white/10' : 'hover:bg-white/5 hover:text-white' }}">
                    <x-icon :name="$item['icon']" class="size-5 {{ $active ? 'text-brand-300' : 'text-slate-400 group-hover:text-brand-300' }}" />
                    {{ $item['label'] }}
                </a>
            @endif
        @endforeach
    </nav>

    <div class="m-4 rounded-2xl bg-white/5 p-4 ring-1 ring-white/10">
        <div class="flex items-center gap-3">
            <div class="grid size-10 place-items-center rounded-full bg-gradient-to-br from-brand-400 to-fuchsia-500 text-sm font-bold text-white">
                {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div class="min-w-0">
                <div class="truncate text-sm font-semibold text-white">{{ auth()->user()->name }}</div>
                <div class="text-xs text-slate-400">{{ auth()->user()->role->label() }}</div>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg bg-white/5 px-3 py-2 text-xs font-semibold text-slate-300 transition hover:bg-white/10 hover:text-white">
                <x-icon name="logout" class="size-4" /> Déconnexion
            </button>
        </form>
    </div>
</aside>

<div class="lg:pl-72">
    <header class="glass sticky top-0 z-10 flex h-16 items-center justify-between px-4 sm:px-8">
        <button @click="nav = true" class="btn-ghost -ml-2 !p-2 lg:hidden" aria-label="Menu"><x-icon name="menu" /></button>
        <div class="hidden text-sm text-slate-500 lg:block dark:text-slate-400">{{ now()->translatedFormat('l j F Y') }}</div>
        <div class="flex items-center gap-2">
            <button @click="$store.theme.toggle()" class="btn-ghost !p-2" aria-label="Changer de thème">
                <x-icon name="moon" class="size-5 dark:hidden" />
                <x-icon name="sun" class="hidden size-5 dark:block" />
            </button>
            @can('documents.manage')
                <a href="{{ route('documents.create') }}" class="btn-primary !py-2"><x-icon name="plus" class="size-4" /> <span class="hidden sm:inline">Nouveau document</span></a>
            @endcan
        </div>
    </header>

    <main class="bg-mesh min-h-[calc(100vh-4rem)] px-4 py-8 sm:px-8">
        <div class="mx-auto max-w-7xl animate-fade-up">
            @if(session('success'))
                <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4500)" x-show="show" x-transition
                     class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                    <x-icon name="check" class="size-5" /> {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-6 flex items-center gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300">
                    <x-icon name="exclamation" class="size-5" /> {{ session('error') }}
                </div>
            @endif

            {{ $slot }}
        </div>
    </main>
</div>
</body>
</html>
