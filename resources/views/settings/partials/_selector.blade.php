<div class="flex flex-col gap-4 py-4">

    <div>
        <h2 class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('lingua::lingua.settings.selector.title') }}</h2>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('lingua::lingua.settings.selector.subtitle') }}
        </p>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">

    {{-- Show flags toggle --}}
{{--    <div class="flex items-start justify-between gap-4">--}}
{{--        <div>--}}
            <flux:switch
                id="show-flags"
                wire:model.live="showFlags"
                :aria-label="__('lingua::lingua.settings.selector.show_flags')"
                :label="__('lingua::lingua.settings.selector.show_flags')"
                :description="__('lingua::lingua.settings.selector.show_flags_description')"
            />
{{--            <label for="show-flags" class="text-sm font-medium text-zinc-900 dark:text-white">--}}
{{--                Show flag icons--}}
{{--            </label>--}}
{{--            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">--}}
{{--                Display country flag icons next to language names in the selector.--}}
{{--            </p>--}}
{{--        </div>--}}
{{--        <div class="flex-shrink-0">--}}

{{--        </div>--}}
{{--    </div>--}}

{{--    <flux:separator variant="subtle"/>--}}

    {{-- Selector mode --}}
    <div class="flex flex-col gap-2">
        <label for="selector-mode" class="text-sm font-medium text-zinc-900 dark:text-white">
            {{ __('lingua::lingua.settings.selector.mode') }}
        </label>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('lingua::lingua.settings.selector.mode_description') }}
        </p>

        <select
            id="selector-mode"
            wire:model="selectorMode"
            class="mt-1 block w-full max-w-xs rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:focus:border-zinc-400"
        >
            @foreach ($this->availableModes as $mode)
                <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
            @endforeach
        </select>

        {{-- Headless mode notice --}}
        @if ($selectorMode === 'headless')
            <div class="mt-2 flex items-start gap-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                </svg>
                <span>{{ __('lingua::lingua.settings.selector.headless_note') }}</span>
            </div>
        @endif
    </div>

    </div>

    <flux:separator variant="subtle"/>

    {{-- Save button --}}
    <div class="flex items-center gap-3">
        <flux:button
            variant="primary" color="green"
            type="button"
            wire:click="save"
        >{{ __('lingua::lingua.settings.selector.save') }}</flux:button>

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
            {{ __('lingua::lingua.settings.selector.saved') }}
        </span>
    </div>

</div>
