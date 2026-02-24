@props([
    'on',
    'delay' => 2000
])

<div
    x-data="{
        targetEvent: @js($on),
        delay: @js($delay),
        shown: false,
        timeout: null,
        showMessage() {
            clearTimeout(this.timeout);
            this.shown = true;
            this.timeout = setTimeout(() => { this.shown = false }, this.delay);
        }
    }"
    x-init="@this.on(targetEvent, () => { showMessage() })"
    x-show.transition.out.opacity.duration.{{ $delay-500 }}ms="shown"
    x-transition:leave.opacity.duration.{{ $delay-500 }}ms
    x-ref="message"
    style="display: none;"
    {{ $attributes->merge(['class' => 'text-sm']) }}
>
    {{ $slot->isEmpty() ? __('language.global.saved') : $slot }}
</div>
