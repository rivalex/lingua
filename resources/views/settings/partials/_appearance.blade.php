{{-- Force dark mode --}}
<x-lingua::card.row
    :title="__('lingua::lingua.settings.appearance.force_dark_mode')"
    :description="__('lingua::lingua.settings.appearance.force_dark_mode_description')">
    <flux:switch wire:model.live="forceDarkMode"/>
</x-lingua::card.row>
