<div class="sticky bottom-0 z-10 border-t border-zinc-200 bg-white/80 px-6 py-4 backdrop-blur-sm dark:border-zinc-700 dark:bg-zinc-900/80">
    <div class="flex items-center justify-end gap-3">

        <span
            x-data="{ show: false }"
            x-on:settings-saved.window="show = true; setTimeout(() => show = false, 2500)"
            x-show="show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="flex items-center gap-1.5 text-sm text-green-600 dark:text-green-400"
            aria-live="polite"
        >
            <flux:icon name="check-circle" size="sm"/>
            {{ __('lingua::lingua.settings.saved') }}
        </span>

        <flux:button
            variant="primary"
            color="green"
            type="button"
            wire:click="save"
        >{{ __('lingua::lingua.settings.save') }}</flux:button>

    </div>
</div>
