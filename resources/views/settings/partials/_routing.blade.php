<div class="flex flex-col gap-6 py-4">

    <div>
        <h2 class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('lingua::lingua.settings.routing.title') }}</h2>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('lingua::lingua.settings.routing.subtitle') }}
        </p>
    </div>

    <flux:switch
        wire:model.live="navigate"
        :label="__('lingua::lingua.settings.routing.navigate')"
        :description="__('lingua::lingua.settings.routing.navigate_description')"
    />

    <flux:switch
        wire:model.live="linksTranslationsEnabled"
        :label="__('lingua::lingua.settings.routing.links_enabled')"
        :description="__('lingua::lingua.settings.routing.links_enabled_description')"
    />

    <flux:input
        wire:model="linksTranslationsRoute"
        :label="__('lingua::lingua.settings.routing.links_route')"
        :description="__('lingua::lingua.settings.routing.links_route_description')"
        placeholder="lingua.translations"
        class="max-w-sm"
    />
    <flux:error name="linksTranslationsRoute"/>

    <flux:input
        wire:model="layout"
        :label="__('lingua::lingua.settings.routing.layout')"
        :description="__('lingua::lingua.settings.routing.layout_description')"
        placeholder="components.layouts.app"
        class="max-w-sm"
    />
    <flux:error name="layout"/>

    <flux:input
        wire:model="uiStickyTop"
        type="text"
        :label="__('lingua::lingua.settings.routing.sticky_top')"
        :description="__('lingua::lingua.settings.routing.sticky_top_description')"
        placeholder="0"
        class="max-w-xs"
    />
    <flux:error name="uiStickyTop"/>

</div>
