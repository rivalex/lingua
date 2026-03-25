@php
    use LaravelLang\Locales\Facades\Locales;
    use Rivalex\Lingua\Facades\Lingua;
@endphp
<section class="flex flex-col gap-4">
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

    @island(name: 'languageSort', always: true)
    <livewire:lingua::language.sort :key="'sortLanguages_'. uniqid()"/>
    @endisland
    <flux:separator/>
    <div
        class="flex flex-col lg:flex-row w-full items-center justify-between sticky top-0 z-1 bg-white dark:bg-zinc-800 py-4 gap-4">
        <div class="flex w-1/4">
            <div class="relative w-full items-center justify-between">
                <flux:input type="search" wire:model.live.debounce.1000ms="search"
                            :placeholder="__('lingua::lingua.global.search')"
                            icon="magnifying-glass" wire:island="languagesRows"
                            name="searchLanguage" id="searchLanguage"/>
            </div>
        </div>
        <div class="flex flex-col lg:flex-row w-max gap-2 items-center">
            <flux:button wire:click="syncToLocal" icon="arrow-path" variant="primary"
                         color="sky">@lang('lingua::lingua.languages.actions.sync.local')</flux:button>
            <flux:button wire:click="syncToDatabase" icon="arrow-path" variant="primary"
                         color="sky">@lang('lingua::lingua.languages.actions.sync.database')</flux:button>
            <flux:button wire:click="updateLanguages" icon="arrow-down-on-square" variant="primary"
                         color="orange">@lang('lingua::lingua.languages.actions.update_lang')</flux:button>
            <livewire:lingua::language.create :key="'newLanguage_'. uniqid()"/>
        </div>
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
    <div class="relative">
        <livewire:lingua::language.table wire:model.live="search" lazy/>
    </div>
</section>
@assets
@once
    <link rel="stylesheet" href="{{ asset('vendor/lingua/css/lingua.min.css') }}">
    <script type="module" src="{{ asset('vendor/lingua/js/lingua.min.js') }}"></script>
@endonce
@endassets
