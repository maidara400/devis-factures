@props(['label', 'value', 'icon', 'tone' => 'brand', 'hint' => null])
@php
    $tones = [
        'brand' => 'bg-brand-50 text-brand-700',
        'emerald' => 'bg-emerald-50 text-emerald-700',
        'amber' => 'bg-amber-50 text-amber-700',
        'rose' => 'bg-rose-50 text-rose-700',
    ];
@endphp
<div class="card p-5">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-sm text-slate-500">{{ $label }}</p>
            <p class="mt-1 text-2xl font-semibold whitespace-nowrap text-slate-900">{{ $value }}</p>
            @if($hint)<p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>@endif
        </div>
        <div class="grid size-9 shrink-0 place-items-center rounded-md {{ $tones[$tone] }}">
            <x-icon :name="$icon" class="size-5" />
        </div>
    </div>
</div>
