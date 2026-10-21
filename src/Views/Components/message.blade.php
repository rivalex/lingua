@props([
    'on',
    'delay' => 2000
])

<div
    x-data="message(@js($on), @js($delay))"
    x-show.transition.out.opacity.duration.{{ $delay-500 }}ms="shown"
    x-transition:leave.opacity.duration.{{ $delay-500 }}ms
    x-ref="message"
    style="display: none;"
    {{ $attributes->merge(['class' => 'text-sm']) }}
>
    {{ $slot->isEmpty() ? __('language.global.saved') : $slot }}
</div>
