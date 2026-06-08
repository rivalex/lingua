<div class="flex items-center gap-3 py-4">
    <flux:button
        variant="primary" color="green"
        type="button"
        wire:click="save"
    >{{ __('lingua::lingua.settings.save') }}</flux:button>

    <span
        x-data="{ show: false }"
        x-on:settings-saved.window="show = true; setTimeout(() => show = false, 2500)"
        x-show="show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="text-sm text-green-600 dark:text-green-400"
        aria-live="polite"
    >
        {{ __('lingua::lingua.settings.saved') }}
    </span>
</div>
