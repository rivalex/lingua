@props([
    'on',
    'delay' => 2000
])

<div
    x-data="{ shown: false, timeout: null }"
    x-init="@this.on('{{ $on }}', () => { clearTimeout(timeout); shown = true; timeout = setTimeout(() => { shown = false }, {{ $delay }}); })"
    x-show.transition.out.opacity.duration.{{ $delay-500 }}ms="shown"
    x-transition:leave.opacity.duration.{{ $delay-500 }}ms
    style="display: none;"
    {{ $attributes->merge(['class' => 'text-sm']) }}
>
    {{ $slot->isEmpty() ? __('language.global.saved') : $slot }}
</div>
