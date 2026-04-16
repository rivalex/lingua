<div class="lingua">
    <section class="flex flex-col gap-4">
        <div class="relative w-full">
            <flux:heading size="xl" level="1">
                <div class="flex flex-row gap-2 items-center">
                    <livewire:lingua::selector.icon :locale="$language->code" size="8" square/>
                    <p>@lang('lingua::lingua.translations.header', ['locale' => $language->native])</p>
                </div>
            </flux:heading>
            <flux:subheading size="lg"
                             class="mb-4">@lang('lingua::lingua.translations.subheader', ['locale' => $language->native])</flux:subheading>
            <flux:separator variant="subtle"/>
        </div>

        {{--    @if(!config('lingua.suppress_pro_nudge', false))--}}
        {{--    <div--}}
        {{--        x-data="{ dismissed: false }"--}}
        {{--        x-show="!dismissed"--}}
        {{--        x-cloak--}}
        {{--        class="flex items-center justify-between gap-4 rounded-lg border border-violet-200 bg-violet-50 px-4 py-3 text-sm dark:border-violet-800 dark:bg-violet-950"--}}
        {{--    >--}}
        {{--        <div class="flex items-center gap-3">--}}
        {{--            <flux:icon.sparkles class="shrink-0 text-violet-500 dark:text-violet-400" size="sm"/>--}}
        {{--            <p class="text-violet-700 dark:text-violet-300">--}}
        {{--                <strong>@lang('lingua::lingua.pro.banner_text')</strong>--}}
        {{--                — @lang('lingua::lingua.pro.banner_features')--}}
        {{--            </p>--}}
        {{--        </div>--}}
        {{--        <div class="flex shrink-0 items-center gap-2">--}}
        {{--            <a--}}
        {{--                href="{{ config('lingua.pro_upgrade_url', 'https://lingua.rivalex.com') }}"--}}
        {{--                target="_blank"--}}
        {{--                rel="noopener noreferrer"--}}
        {{--                class="inline-flex items-center gap-1 rounded-md bg-violet-600 px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-violet-700"--}}
        {{--            >@lang('lingua::lingua.pro.banner_cta')</a>--}}
        {{--            <button--}}
        {{--                x-on:click="dismissed = true"--}}
        {{--                class="text-violet-400 transition-colors hover:text-violet-600 dark:hover:text-violet-300"--}}
        {{--                aria-label="Dismiss"--}}
        {{--            ><flux:icon.x-mark size="sm"/></button>--}}
        {{--        </div>--}}
        {{--    </div>--}}
        {{--    @endif--}}

        <div
            class="flex flex-col lg:flex-row w-full items-center justify-between sticky top-0 z-1 bg-white dark:bg-zinc-800 py-4 gap-4">
            <div class="flex flex-col lg:flex-row w-full lg:w-max items-center gap-4">
                <flux:input type="search" wire:model.live.debounce.1000ms="search" class="search-input"
                            :placeholder="__('lingua::lingua.global.search')"
                            icon="magnifying-glass"
                            wire:island="translationTable"
                            name="searchTranslations" id="searchTranslations"/>
                <flux:select wire:model.change.live="currentLocale"
                             :variant="Flux::pro() ? 'listbox' : null"
                             :searchable="Flux::pro()"
                             wire:island="translationTable">
                    @foreach($availableLocale as $code => $localeItem)
                        <flux:select.option :value="$code" :key="$code">{{ $localeItem }}</flux:select.option>
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
                        <flux:select.option :value="$groupItem" :key="$groupItem">{{ $groupItem }}</flux:select.option>
                    @endforeach
                </flux:select>
                @if($currentLocale !== linguaDefaultLocale())
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
                        <flux:icon.check-circle size="sm"/>
                        <p>@lang('lingua::lingua.translations.status.translation_added')</p>
                    </div>
                </flux:badge>
            </x-lingua::message>
            <x-lingua::message on="translation_add_fail">
                <flux:badge color="red">
                    <div class="flex items-center gap-2" style="white-space: normal;">
                        <flux:icon.exclamation-circle size="sm"/>
                        <p>@lang('lingua::lingua.translations.status.translation_add_fail')</p>
                    </div>
                </flux:badge>
            </x-lingua::message>
            <x-lingua::message on="translation_updated">
                <flux:badge color="green">
                    <div class="flex items-center gap-2" style="white-space: normal;">
                        <flux:icon.check-circle size="sm"/>
                        <p>@lang('lingua::lingua.translations.status.translation_updated')</p>
                    </div>
                </flux:badge>
            </x-lingua::message>
            <x-lingua::message on="translation_update_fail">
                <flux:badge color="red">
                    <div class="flex items-center gap-2" style="white-space: normal;">
                        <flux:icon.exclamation-circle size="sm"/>
                        <p>@lang('lingua::lingua.translations.status.translation_update_fail')</p>
                    </div>
                </flux:badge>
            </x-lingua::message>
            <x-lingua::message on="translation_deleted">
                <flux:badge color="green">
                    <div class="flex items-center gap-2" style="white-space: normal;">
                        <flux:icon.check-circle size="sm"/>
                        <p>@lang('lingua::lingua.translations.status.translation_deleted')</p>
                    </div>
                </flux:badge>
            </x-lingua::message>
            <x-lingua::message on="translation_delete_fail">
                <flux:badge color="red">
                    <div class="flex items-center gap-2" style="white-space: normal;">
                        <flux:icon.exclamation-circle size="sm"/>
                        <p>@lang('lingua::lingua.translations.status.translation_delete_fail')</p>
                    </div>
                </flux:badge>
            </x-lingua::message>
            <x-lingua::message on="translation_locale_deleted">
                <flux:badge color="green">
                    <div class="flex items-center gap-2" style="white-space: normal;">
                        <flux:icon.check-circle size="sm"/>
                        <p>@lang('lingua::lingua.translations.status.translation_locale_deleted', ['locale' => $language->native])</p>
                    </div>
                </flux:badge>
            </x-lingua::message>
            <x-lingua::message on="translation_locale_delete_fail">
                <flux:badge color="red">
                    <div class="flex items-center gap-2" style="white-space: normal;">
                        <flux:icon.exclamation-circle size="sm"/>
                        <p>@lang('lingua::lingua.translations.status.translation_locale_delete_fail', ['locale' => $language->native])</p>
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
                            <livewire:lingua::selector.icon :locale="Lingua::getDefaultLocale()" size="sm" square/>
                        </div>
                    </flux:table.column>
                    <flux:table.column>
                        <div class="flex flex-row gap-2 items-center">
                            <p>@lang('lingua::lingua.translations.table.columns.translation')</p>
                            <livewire:lingua::selector.icon :locale="$language->code" size="sm" square/>
                        </div>
                    </flux:table.column>
                    <flux:table.column style="width: 10%" align="center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             class="lucide lucide-cog-icon lucide-cog">
                            <path d="M11 10.27 7 3.34"/>
                            <path d="m11 13.73-4 6.93"/>
                            <path d="M12 22v-2"/>
                            <path d="M12 2v2"/>
                            <path d="M14 12h8"/>
                            <path d="m17 20.66-1-1.73"/>
                            <path d="m17 3.34-1 1.73"/>
                            <path d="M2 12h2"/>
                            <path d="m20.66 17-1.73-1"/>
                            <path d="m20.66 7-1.73 1"/>
                            <path d="m3.34 17 1.73-1"/>
                            <path d="m3.34 7 1.73 1"/>
                            <circle cx="12" cy="12" r="2"/>
                            <circle cx="12" cy="12" r="8"/>
                        </svg>
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
</div>
@assets
@once
    <link rel="stylesheet" href="{{ route('lingua.assets', 'css/lingua.min.css') }}">
    <script type="module" src="{{ route('lingua.assets', 'js/lingua.min.js') }}"></script>
@endonce
@endassets
