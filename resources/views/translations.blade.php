<section class="flex flex-col gap-4">
    <div class="relative w-full">
        <flux:heading size="xl" level="1">
            <div class="flex flex-row gap-2 items-center">
                @svg('flag-language-'.languageCode($language->code), 'w-8 h-8')
                <p>@lang('lingua::lingua.translations.header', ['locale' => $language->native])</p>
            </div>
        </flux:heading>
        <flux:subheading size="lg"
                         class="mb-4">@lang('lingua::lingua.translations.subheader', ['locale' => $language->native])</flux:subheading>
        <flux:separator variant="subtle"/>
    </div>

    <div
        class="flex flex-col lg:flex-row w-full items-center justify-between sticky top-0 z-1 bg-white dark:bg-zinc-800 py-4 gap-4">
        <div class="flex flex-col lg:flex-row w-full lg:w-max items-center gap-4">
            <flux:input type="search" wire:model.live.debounce.1000ms="search" class="search-input"
                        :placeholder="__('lingua::lingua.global.search')"
                        icon="magnifying-glass" wire:island="translationTable"
                        name="searchTranslations" id="searchTranslations"/>
            <flux:select wire:model.change.live="currentLocale"
                         :variant="Flux::pro() ? 'listbox' : null"
                         :searchable="Flux::pro()"
                         wire:island="translationTable">
                @foreach($availableLocale as $code => $localeItem)
                    <flux:select.option :value="$code" wire:key="{{ $code }}">{{ $localeItem }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.change.live="group"
                         :variant="Flux::pro() ? 'listbox' : null"
                         :searchable="Flux::pro()"
                         clearable
                         :placeholder="Flux::pro() ? __('lingua::lingua.translations.group.placeholder') : null"
                         wire:island="translationTable">
                @if(!Flux::pro())
                    <flux:select.option
                        value="">@lang('lingua::lingua.translations.group.all_groups')</flux:select.option>
                @endif
                @foreach($availableGroups as $groupItem)
                    <flux:select.option value="{{ $groupItem }}"
                                        wire:key="{{ $groupItem }}">{{ $groupItem }}</flux:select.option>
                @endforeach
            </flux:select>
            @if($currentLocale !== defaultLocale())
                <flux:field variant="inline" class="flex items-center gap-2 w-fit">
                    <flux:label><p
                            style="white-space: nowrap; font-weight: 400;">@lang('lingua::lingua.translations.table.show_only_missing')</p>
                    </flux:label>
                    <flux:switch wire:model.change.live="showOnlyMissing" wire:island="translationTable"/>
                    <flux:error name="showOnlyMissing"/>
                </flux:field>
            @endif
        </div>
        <div class="w-max gap-2">
            <livewire:lingua::translation.create :key="'newTranslation'" :$group/>
        </div>
    </div>
    <div class="flex flex-col w-full gap-2">
        <x-lingua::message on="translation_added">
            <flux:badge color="green">
                <div class="flex items-center gap-2" style="white-space: normal;">
                    <flux:icon icon="check-circle" size="sm"/>
                    <p>@lang('language.new_language_added')</p>
                </div>
            </flux:badge>
        </x-lingua::message>
        <x-lingua::message on="translation_added_fail">
            <flux:badge color="red">
                <div class="flex items-center gap-2" style="white-space: normal;">
                    <flux:icon icon="exclamation-circle" size="sm"/>
                    <p>@lang('language.new_language_added_fail')</p>
                </div>
            </flux:badge>
        </x-lingua::message>
        <x-lingua::message on="translation_updated">
            <flux:badge color="green">
                <div class="flex items-center gap-2" style="white-space: normal;">
                    <flux:icon icon="check-circle" size="sm"/>
                    <p>@lang('language.sync_local_done')</p>
                </div>
            </flux:badge>
        </x-lingua::message>
        <x-lingua::message on="translation_updated_fail">
            <flux:badge color="red">
                <div class="flex items-center gap-2" style="white-space: normal;">
                    <flux:icon icon="exclamation-circle" size="sm"/>
                    <p>@lang('language.sync_local_fail')</p>
                </div>
            </flux:badge>
        </x-lingua::message>
        <x-lingua::message on="translation_deleted">
            <flux:badge color="green">
                <div class="flex items-center gap-2" style="white-space: normal;">
                    <flux:icon icon="check-circle" size="sm"/>
                    <p>@lang('language.sync_database_done')</p>
                </div>
            </flux:badge>
        </x-lingua::message>
        <x-lingua::message on="translation_deleted_fail">
            <flux:badge color="red">
                <div class="flex items-center gap-2" style="white-space: normal;">
                    <flux:icon icon="exclamation-circle" size="sm"/>
                    <p>@lang('language.lang_updated')</p>
                </div>
            </flux:badge>
        </x-lingua::message>
        <x-lingua::message on="translation_deleted_local">
            <flux:badge color="green">
                <div class="flex items-center gap-2" style="white-space: normal;">
                    <flux:icon icon="check-circle" size="sm"/>
                    <p>@lang('language.lang_updated')</p>
                </div>
            </flux:badge>
        </x-lingua::message>
        <x-lingua::message on="translation_deleted_local_fail">
            <flux:badge color="red">
                <div class="flex items-center gap-2" style="white-space: normal;">
                    <flux:icon icon="exclamation-circle" size="sm"/>
                    <p>@lang('language.lang_updated')</p>
                </div>
            </flux:badge>
        </x-lingua::message>
    </div>
    <div class="relative">
        @island(name: 'translationTable', always: true)
        <flux:table :paginate="$this->translations()" class="w-full">
            <flux:table.columns>
                <flux:table.column
                    style="width: 20%;">@lang('lingua::lingua.translations.table.columns.group_key')</flux:table.column>
                <flux:table.column style="width: 30%;">
                    <div class="flex flex-row gap-2 items-center">
                        <p>@lang('lingua::lingua.translations.table.columns.default')</p>
                        @svg('flag-language-'.languageCode(), "h-4")
                    </div>
                </flux:table.column>
                <flux:table.column>
                    <div class="flex flex-row gap-2 items-center">
                        <p>@lang('lingua::lingua.translations.table.columns.translation')</p>
                        @svg('flag-language-'.languageCode($currentLocale), "h-4")
                    </div>
                </flux:table.column>
                <flux:table.column style="width: 10%" align="center">
                    <flux:icon.cog/>
                </flux:table.column>
            </flux:table.columns>

            @island(name: 'translationRows', always: true)
            <flux:table.rows>
                @foreach ($this->translations as $translation)
                    <livewire:lingua::translation.row :$currentLocale :$translation
                                                         :key="'translationRow_'. $translation->id"/>
                @endforeach
            </flux:table.rows>
            @endisland
        </flux:table>
        @endisland
    </div>
</section>
@assets
@once
    <link rel="stylesheet" href="{{ asset('vendor/lingua/css/lingua.min.css') }}">
    <script type="module" src="{{ asset('vendor/lingua/js/lingua.min.js') }}"></script>
@endonce
@endassets
