@props(['label', 'value', 'icon', 'tone' => 'brand', 'hint' => null])
@php
    $tones = [
        'brand' => 'from-brand-500 to-violet-600 shadow-brand-500/30',
        'emerald' => 'from-emerald-500 to-teal-600 shadow-emerald-500/30',
        'amber' => 'from-amber-400 to-orange-500 shadow-amber-500/30',
        'rose' => 'from-rose-500 to-pink-600 shadow-rose-500/30',
    ];
@endphp
<div class="card group relative overflow-hidden p-5 transition hover:-translate-y-0.5 hover:shadow-lg">
    <div class="flex items-start justify-between">
        <div>
            <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase dark:text-slate-400">{{ $label }}</p>
            <p class="mt-2 text-xl font-bold tracking-tight whitespace-nowrap text-slate-900 dark:text-white">{{ $value }}</p>
            @if($hint)<p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $hint }}</p>@endif
        </div>
        <div class="grid size-11 place-items-center rounded-xl bg-gradient-to-br {{ $tones[$tone] }} text-white shadow-lg">
            <x-icon :name="$icon" class="size-5" />
        </div>
    </div>
</div>
