{{-- Navigation menu --}}
<x-lingua::card.row
    :title="__('lingua::lingua.settings.routing.nav_menu')"
    :description="__('lingua::lingua.settings.routing.nav_menu_description')">
    <flux:switch wire:model.live="navEnabled"/>
</x-lingua::card.row>

{{-- Navigate --}}
<x-lingua::card.row
    :title="__('lingua::lingua.settings.routing.navigate')"
    :description="__('lingua::lingua.settings.routing.navigate_description')">
    <flux:switch wire:model.live="navigate"/>
</x-lingua::card.row>

{{-- Translation links (toggle + route name) --}}
<x-lingua::card.row
    :title="__('lingua::lingua.settings.routing.links_enabled')"
    :description="__('lingua::lingua.settings.routing.links_enabled_description')">
    <flux:switch wire:model.live="linksTranslationsEnabled"/>
    <flux:input
        wire:model="linksTranslationsRoute"
        :label="__('lingua::lingua.settings.routing.links_route')"
        :description="__('lingua::lingua.settings.routing.links_route_description')"
        placeholder="lingua.translations"
        class="max-w-sm"
    />
    <flux:error name="linksTranslationsRoute"/>
</x-lingua::card.row>

{{-- Blade layout --}}
<x-lingua::card.row
    :title="__('lingua::lingua.settings.routing.layout')"
    :description="__('lingua::lingua.settings.routing.layout_description')">
    <flux:input
        wire:model="layout"
        placeholder="components.layouts.app"
        class="max-w-sm"
    />
    <flux:error name="layout"/>
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
