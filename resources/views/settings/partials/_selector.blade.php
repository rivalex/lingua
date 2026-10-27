{{-- Show flags row --}}
<x-lingua::card.row
    :title="__('lingua::lingua.settings.selector.show_flags')"
    :description="__('lingua::lingua.settings.selector.show_flags_description')">
    <flux:switch
        id="show-flags"
        wire:model.live="showFlags"
        :aria-label="__('lingua::lingua.settings.selector.show_flags')"
    />
</x-lingua::card.row>

{{-- Selector mode row --}}
<x-lingua::card.row
    :title="__('lingua::lingua.settings.selector.mode')"
    :description="__('lingua::lingua.settings.selector.mode_description')">
    <x-lingua::select
        id="selector-mode"
        wire:model.live="selectorMode"
        class="max-w-xs">
        @foreach ($this->availableModes as $mode)
            <x-lingua::select.option :value="$mode->value">{{ $mode->label() }}</x-lingua::select.option>
        @endforeach
    </x-lingua::select>

    @if ($selectorMode === 'headless')
    <div class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-3 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
        <flux:icon name="exclamation-triangle" size="sm" class="mt-0.5 shrink-0"/>
        <span>{{ __('lingua::lingua.settings.selector.headless_note') }}</span>
    </div>
    @endif
</x-lingua::card.row>
