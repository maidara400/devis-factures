@props(['icon' => 'document', 'title', 'text' => null])
<div class="flex flex-col items-center px-6 py-16 text-center">
    <div class="grid size-16 place-items-center rounded-2xl bg-brand-50 text-brand-500 dark:bg-brand-500/10"><x-icon :name="$icon" class="size-8" /></div>
    <h3 class="mt-4 text-base font-semibold text-slate-900 dark:text-white">{{ $title }}</h3>
    @if($text)<p class="mt-1 max-w-sm text-sm text-slate-500 dark:text-slate-400">{{ $text }}</p>@endif
    <div class="mt-5">{{ $slot }}</div>
</div>
