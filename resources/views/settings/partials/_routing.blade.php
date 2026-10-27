{{-- Navigation menu --}}
<x-lingua::card.row
    :title="__('lingua::lingua.settings.routing.nav_menu')"
    :description="__('lingua::lingua.settings.routing.nav_menu_description')">
    <flux:switch wire:model.live="navEnabled"/>
</x-lingua::card.row>

{{-- Sticky top offset --}}
<x-lingua::card.row
    :title="__('lingua::lingua.settings.routing.sticky_top')"
    :description="__('lingua::lingua.settings.routing.sticky_top_description')">
    <flux:input
        wire:model="uiStickyTop"
        type="text"
        placeholder="0"
        class="max-w-xs"
    />
    <flux:error name="uiStickyTop"/>
</x-lingua::card.row>
