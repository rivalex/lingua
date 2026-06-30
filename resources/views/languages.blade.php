@php
    use Rivalex\Lingua\Facades\Lingua;
@endphp
<div class="lingua">
    <x-lingua::branding />

    <section class="flex flex-col gap-6">
        <div class="relative w-full">
            <flux:heading size="xl" level="1">@lang('lingua::lingua.languages.title')</flux:heading>
            <flux:subheading size="lg" class="mb-6">
                <div class="flex-col gap-2">
                    <p>@lang('lingua::lingua.languages.subtitle')</p>
                    <div class="flex items-center gap-2 text-sm">
                        <p>@lang('lingua::lingua.languages.default_language'):</p>
                        <livewire:lingua::selector.icon class="default_language" :locale="app()->currentLocale()" square
                                                        size="md"/>
                    </div>
                </div>
            </flux:subheading>
        </div>

        <x-lingua::nav />

        @island(name: 'languageSort', always: true)
        <livewire:lingua::language.sort :key="'sortLanguages_'. uniqid()"/>
        @endisland
        <div class="-mx-4 px-4 sm:px-6 lg:px-8 py-4 flex flex-col gap-3 bg-white/70 dark:bg-zinc-900/70 border-b border-zinc-200/50 dark:border-white/10">
            {{-- Row 1: search + new language (always visible) --}}
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex-1 min-w-48">
                    <flux:input type="search" wire:model.live.debounce.1000ms="search"
                                :placeholder="__('lingua::lingua.global.search')"
                                icon="magnifying-glass" wire:island="languagesRows"
                                name="searchLanguage" id="searchLanguage"/>
                </div>
                <div class="shrink-0">
                    <livewire:lingua::language.create :key="'newLanguage_'. uniqid()" />
                </div>
            </div>
            {{-- Row 2: sync/update actions (DB mode only) --}}
            @unless($fileMode)
            <div class="flex flex-wrap items-center gap-2">
                <flux:button wire:click="syncToLocal" icon="arrow-path" variant="primary"
                             color="sky">@lang('lingua::lingua.languages.actions.sync.local')</flux:button>
                <flux:button wire:click="syncToDatabase" icon="arrow-path" variant="primary"
                             color="sky">@lang('lingua::lingua.languages.actions.sync.database')</flux:button>
                <flux:button wire:click="updateLanguages" icon="arrow-down-on-square" variant="primary"
                             color="orange">@lang('lingua::lingua.languages.actions.update_lang')</flux:button>
            </div>
            @endunless
        </div>
        <div class="flex flex-col w-full gap-2">
            <x-lingua::message on="language_added">
                <flux:badge color="green">
                    <div class="flex items-center gap-2" style="white-space: normal;">
                        <flux:icon icon="check-circle" size="sm"/>
                        <p>@lang('lingua::lingua.languages.create.save.new_language_added')</p>
                    </div>
                </flux:badge>
            </x-lingua::message>
            <x-lingua::message on="language_added_fail">
                <flux:badge color="red">
                    <div class="flex items-center gap-2" style="white-space: normal;">
                        <flux:icon icon="exclamation-circle" size="sm"/>
                        <p>@lang('lingua::lingua.languages.create.save.new_language_added_fail')</p>
                    </div>
                </flux:badge>
            </x-lingua::message>
            <x-lingua::message on="synced_local">
                <flux:badge color="green">
                    <div class="flex items-center gap-2" style="white-space: normal;">
                        <flux:icon icon="check-circle" size="sm"/>
                        <p>@lang('lingua::lingua.languages.actions.status.sync_local_done')</p>
                    </div>
                </flux:badge>
            </x-lingua::message>
            <x-lingua::message on="synced_local_fail">
                <flux:badge color="red">
                    <div class="flex items-center gap-2" style="white-space: normal;">
                        <flux:icon icon="exclamation-circle" size="sm"/>
                        <p>@lang('lingua::lingua.languages.actions.status.sync_local_fail')</p>
                    </div>
                </flux:badge>
            </x-lingua::message>
            <x-lingua::message on="synced_database">
                <flux:badge color="green">
                    <div class="flex items-center gap-2" style="white-space: normal;">
                        <flux:icon icon="check-circle" size="sm"/>
                        <p>@lang('lingua::lingua.languages.actions.status.sync_database_done')</p>
                    </div>
                </flux:badge>
            </x-lingua::message>
            <x-lingua::message on="synced_database_fail">
                <flux:badge color="red">
                    <div class="flex items-center gap-2" style="white-space: normal;">
                        <flux:icon icon="exclamation-circle" size="sm"/>
                        <p>@lang('lingua::lingua.languages.actions.status.sync_database_fail')</p>
                    </div>
                </flux:badge>
            </x-lingua::message>
            <x-lingua::message on="lang_updated">
                <flux:badge color="green">
                    <div class="flex items-center gap-2" style="white-space: normal;">
                        <flux:icon icon="check-circle" size="sm"/>
                        <p>@lang('lingua::lingua.languages.actions.status.lang_updated')</p>
                    </div>
                </flux:badge>
            </x-lingua::message>
            <x-lingua::message on="lang_updated_fail">
                <flux:badge color="red">
                    <div class="flex items-center gap-2" style="white-space: normal;">
                        <flux:icon icon="exclamation-circle" size="sm"/>
                        <p>@lang('lingua::lingua.languages.actions.status.lang_updated_fail')</p>
                    </div>
                </flux:badge>
            </x-lingua::message>
        </div>
        <livewire:lingua::language.table wire:model.live="search" lazy/>
    </section>
</div>
@assets
@once
    <link rel="stylesheet" href="{{ linguaAssetUrl('css/lingua.min.css') }}">
    <script type="module" src="{{ linguaAssetUrl('js/lingua.min.js') }}"></script>
@endonce
@endassets
