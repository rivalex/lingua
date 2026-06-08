<div class="flex flex-col gap-6 py-4">

    <div>
        <h2 class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('lingua::lingua.settings.selector.title') }}</h2>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('lingua::lingua.settings.selector.subtitle') }}
        </p>
    </div>

    <flux:switch
        id="show-flags"
        wire:model.live="showFlags"
        :aria-label="__('lingua::lingua.settings.selector.show_flags')"
        :label="__('lingua::lingua.settings.selector.show_flags')"
        :description="__('lingua::lingua.settings.selector.show_flags_description')"
    />

    <x-lingua::select
        id="selector-mode"
        wire:model.live="selectorMode"
        :label="__('lingua::lingua.settings.selector.mode')"
        :description="__('lingua::lingua.settings.selector.mode_description')"
        class="max-w-xs">
        @foreach ($this->availableModes as $mode)
            <x-lingua::select.option :value="$mode->value">{{ $mode->label() }}</x-lingua::select.option>
        @endforeach
    </x-lingua::select>

    @if ($selectorMode === 'headless')
        <div class="flex items-start gap-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
            <svg class="mt-0.5 h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
            </svg>
            <span>{{ __('lingua::lingua.settings.selector.headless_note') }}</span>
        </div>
    @endif

</div>
