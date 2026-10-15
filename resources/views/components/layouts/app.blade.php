@props(['title' => null])
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen" x-data="{ nav: false }">
@php
    $nav = [
        ['route' => 'dashboard', 'match' => 'dashboard', 'label' => 'Tableau de bord', 'icon' => 'home', 'can' => null],
        ['route' => 'documents.index', 'match' => 'documents.*', 'label' => 'Devis et factures', 'icon' => 'receipt', 'can' => 'documents.view'],
        ['route' => 'clients.index', 'match' => 'clients.*', 'label' => 'Clients', 'icon' => 'users', 'can' => 'clients.view'],
        ['route' => 'articles.index', 'match' => 'articles.*', 'label' => 'Articles', 'icon' => 'cube', 'can' => 'articles.view'],
        ['route' => 'users.index', 'match' => 'users.*', 'label' => 'Utilisateurs', 'icon' => 'shield', 'can' => 'users.manage'],
    ];
@endphp

<div x-show="nav" x-cloak @click="nav = false" class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden"></div>

<aside :class="nav ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
       class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-slate-200 bg-white transition-transform lg:z-20">
    <div class="flex h-16 items-center border-b border-slate-200 px-5">
        <span class="text-lg font-semibold text-slate-900">{{ config('app.name') }}</span>
    </div>

    <nav class="flex-1 space-y-0.5 p-3">
        @foreach($nav as $item)
            @if(! $item['can'] || auth()->user()->can($item['can']))
                @php($active = request()->routeIs($item['match']))
                <a href="{{ route($item['route']) }}"
                   class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium
                          {{ $active ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <x-icon :name="$item['icon']" class="size-5 {{ $active ? 'text-brand-600' : 'text-slate-400' }}" />
                    {{ $item['label'] }}
                </a>
            @endif
        @endforeach
    </nav>

    <div class="border-t border-slate-200 p-4">
        <div class="text-sm font-medium text-slate-900">{{ auth()->user()->name }}</div>
        <div class="text-xs text-slate-500">{{ auth()->user()->role->label() }}</div>
        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button class="btn-secondary w-full !py-1.5 text-xs"><x-icon name="logout" class="size-4" /> Déconnexion</button>
        </form>
    </div>
</aside>

<div class="lg:pl-64">
    <header class="sticky top-0 z-10 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-8">
        <button @click="nav = true" class="btn-ghost -ml-2 !p-2 lg:hidden" aria-label="Menu"><x-icon name="menu" /></button>
        <div class="hidden text-sm text-slate-500 lg:block">{{ now()->translatedFormat('l j F Y') }}</div>
        @can('documents.manage')
            <a href="{{ route('documents.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> <span class="hidden sm:inline">Nouveau document</span></a>
        @endcan
    </header>

    <main class="min-h-[calc(100vh-4rem)] px-4 py-8 sm:px-8">
        <div class="mx-auto max-w-7xl">
            @if(session('success'))
                <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4500)" x-show="show"
                     class="mb-6 flex items-center gap-3 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    <x-icon name="check" class="size-5" /> {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-6 flex items-center gap-3 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <x-icon name="exclamation" class="size-5" /> {{ session('error') }}
                </div>
            @endif

            {{ $slot }}
        </div>
    </main>
</div>
</body>
</html>
