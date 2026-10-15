<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion · {{ config('company.name') }}</title>
    <script>
        try { if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark'); } catch (e) {}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
<div class="grid min-h-screen lg:grid-cols-2">
    {{-- Hero --}}
    <div class="relative hidden overflow-hidden bg-slate-950 p-12 text-white lg:flex lg:flex-col lg:justify-between">
        <div class="absolute inset-0 bg-[radial-gradient(at_20%_10%,rgb(99_102_241/0.55),transparent_50%),radial-gradient(at_90%_30%,rgb(192_38_211/0.4),transparent_45%),radial-gradient(at_40%_100%,rgb(14_165_233/0.35),transparent_50%)]"></div>
        <div class="absolute -right-24 -bottom-24 size-96 rounded-full border border-white/10"></div>
        <div class="absolute -right-10 -bottom-10 size-72 rounded-full border border-white/10"></div>

        <div class="relative flex items-center gap-3">
            <div class="grid size-11 place-items-center rounded-xl bg-white/10 ring-1 ring-white/20 backdrop-blur"><x-icon name="sparkles" class="size-6" /></div>
            <span class="text-lg font-bold tracking-tight">Devisio</span>
        </div>

        <div class="relative max-w-md animate-fade-up">
            <h1 class="text-4xl leading-tight font-bold tracking-tight">Vos devis et factures,<br><span class="bg-gradient-to-r from-brand-300 to-fuchsia-300 bg-clip-text text-transparent">sans friction.</span></h1>
            <p class="mt-4 text-slate-300">Créez, envoyez et suivez vos documents commerciaux. Numérotation automatique, PDF élégants, suivi des paiements.</p>

            <div class="glass mt-10 rounded-2xl !border-white/10 !bg-white/5 p-5">
                <div class="flex items-center justify-between text-xs text-slate-300"><span>FAC-{{ now()->year }}-0042</span><span class="rounded-full bg-emerald-400/15 px-2.5 py-1 font-semibold text-emerald-300">Payé</span></div>
                <div class="mt-3 text-3xl font-bold">1 250 000 <span class="text-base font-medium text-slate-400">FCFA</span></div>
                <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-white/10"><div class="h-full w-4/5 rounded-full bg-gradient-to-r from-brand-400 to-fuchsia-400"></div></div>
            </div>
        </div>

        <p class="relative text-xs text-slate-500">© {{ now()->year }} {{ config('company.name') }}</p>
    </div>

    {{-- Form --}}
    <div class="bg-mesh flex items-center justify-center px-6 py-12">
        <div class="w-full max-w-sm animate-fade-up">
            <div class="mb-8 lg:hidden flex items-center gap-2">
                <div class="grid size-10 place-items-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-glow"><x-icon name="sparkles" class="size-5" /></div>
                <span class="text-lg font-bold">Devisio</span>
            </div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Bon retour 👋</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Connectez-vous pour accéder à votre espace.</p>

            <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                @csrf
                <x-input label="Adresse e-mail" name="email" type="email" required autofocus autocomplete="username" placeholder="vous@entreprise.sn" />
                <x-input label="Mot de passe" name="password" type="password" required autocomplete="current-password" placeholder="••••••••" />
                <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                    <input type="checkbox" name="remember" class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"> Se souvenir de moi
                </label>
                <button class="btn-primary w-full !py-3">Se connecter <x-icon name="arrow-right" class="size-4" /></button>
            </form>

            @if(app()->environment('local'))
                <div class="mt-8 rounded-xl border border-dashed border-brand-300 bg-brand-50/60 p-4 text-xs text-slate-600 dark:border-brand-500/30 dark:bg-brand-500/5 dark:text-slate-400">
                    <p class="mb-2 font-semibold text-brand-700 dark:text-brand-300">Comptes de démonstration</p>
                    <p><code>admin@example.com</code> · Administrateur</p>
                    <p><code>manager@example.com</code> · Gestionnaire</p>
                    <p><code>comptable@example.com</code> · Comptable</p>
                    <p class="mt-1">Mot de passe : <code>Password123</code></p>
                </div>
            @endif
        </div>
    </div>
</div>
</body>
</html>
