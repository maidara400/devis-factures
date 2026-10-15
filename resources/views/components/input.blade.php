@props(['label', 'name', 'type' => 'text', 'value' => null, 'hint' => null])
@php($id = 'f_'.str_replace(['[', ']', '.'], '_', $name))
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="label">{{ $label }}@if($attributes->has('required'))<span class="text-rose-500"> *</span>@endif</label>
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
           value="{{ $type === 'password' ? '' : old($name, $value) }}"
           {{ $attributes->except('class')->merge(['class' => 'input '.($errors->has($name) ? 'border-rose-400 ring-4 ring-rose-500/10' : '')]) }}>
    @if($hint)<p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>@endif
    @error($name)<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
</div>
