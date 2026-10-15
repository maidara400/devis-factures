<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center px-4 py-12">
    <div class="w-full max-w-sm">
        <h1 class="text-center text-2xl font-semibold text-slate-900">{{ config('app.name') }}</h1>
        <p class="mt-1 text-center text-sm text-slate-500">Gestion des devis et factures</p>

        <form method="POST" action="{{ route('login') }}" class="card mt-8 space-y-4 p-6">
            @csrf
            <x-input label="Adresse e-mail" name="email" type="email" required autofocus autocomplete="username" />
            <x-input label="Mot de passe" name="password" type="password" required autocomplete="current-password" />
            <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-600"> Se souvenir de moi
            </label>
            <button class="btn-primary w-full">Se connecter</button>
        </form>

        @if(app()->environment('local'))
            <div class="mt-6 rounded-md border border-slate-200 bg-white p-4 text-xs text-slate-600">
                <p class="mb-1 font-medium text-slate-800">Comptes de démonstration</p>
                <p><code>admin@example.com</code> — Administrateur</p>
                <p><code>manager@example.com</code> — Gestionnaire</p>
                <p><code>comptable@example.com</code> — Comptable</p>
                <p class="mt-1">Mot de passe : <code>Password123</code></p>
            </div>
        @endif
    </div>
</body>
</html>
