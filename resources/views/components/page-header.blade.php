@props(['title', 'subtitle' => null, 'back' => null])
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        @if($back)
            <a href="{{ $back }}" class="mb-2 inline-flex items-center gap-1.5 text-xs font-semibold text-brand-600 hover:underline dark:text-brand-300">
                <x-icon name="arrow-left" class="size-3.5" /> Retour
            </a>
        @endif
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl dark:text-white">{{ $title }}</h1>
        @if($subtitle)<p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>@endif
    </div>
    <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
</div>
