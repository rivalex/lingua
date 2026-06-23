<div class="lingua">
    <x-lingua::branding />

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

        {{-- lingua extension hook: translation.tabs --}}
        @foreach ($linguaExtensions->allTranslationTabComponents() as $cls)
            <livewire:dynamic-component :component="$cls" :key="'ext_tab_'.$cls"/>
        @endforeach

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

        @php
            $stickyRaw = \Rivalex\Lingua\Models\LinguaSetting::get(
                \Rivalex\Lingua\Models\LinguaSetting::KEY_UI_STICKY_TOP,
                config('lingua.ui.sticky_top', 0),
            );
            $stickyTop = is_numeric($stickyRaw) ? $stickyRaw.'rem' : $stickyRaw;
        @endphp
        <div
            class="-mx-4 px-4 sm:px-6 lg:px-8 sticky z-30 backdrop-blur-md bg-white/70 dark:bg-zinc-900/70 border-b border-zinc-200/50 dark:border-white/10 py-4"
            style="top: {{ $stickyTop }};">
            <div class="grid grid-cols-12 w-full gap-4 items-center">
                <div class="col-span-12 lg:col-span-3">
                    <flux:input type="search" wire:model.blur.live="search" class="w-full"
                                :placeholder="__('lingua::lingua.global.search')"
                                icon="magnifying-glass"
                                wire:island="translationTable"
                                name="searchTranslations" id="searchTranslations"/>
                </div>
                <div class="col-span-12 lg:col-span-2">
                    <x-lingua::select wire:model.change.live="currentLocale"
                                      searchable
                                      wire:island="translationTable">
                        @foreach($availableLocale as $code => $localeItem)
                            <x-lingua::select.option :value="$code" :label="$localeItem">{{ $localeItem }}</x-lingua::select.option>
                        @endforeach
                    </x-lingua::select>
                </div>
                <div class="col-span-12 lg:col-span-2">
                    <x-lingua::select wire:model.change.live="group"
                                      searchable clearable
                                      :placeholder="__('lingua::lingua.translations.group.placeholder')"
                                      wire:island="translationTable">
                        @foreach($availableGroups as $groupItem)
                            <x-lingua::select.option :value="$groupItem" :label="$groupItem">{{ $groupItem }}</x-lingua::select.option>
                        @endforeach
                    </x-lingua::select>
                </div>
                <div class="col-span-12 lg:col-span-2">
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
                <div class="col-span-12 lg:col-span-3">
                    <livewire:lingua::translation.create :key="'newTranslation'" :$group/>
                    {{-- lingua extension hook: translation.actions --}}
                    @foreach ($linguaExtensions->allTranslationActionComponents() as $cls)
                        <livewire:dynamic-component :component="$cls" :key="'ext_action_'.$cls"/>
                    @endforeach
                </div>
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
                        <livewire:lingua::translation.row :$currentLocale :translation-identity="$translation->identity()"
                                                          :key="'translationRow_'.md5($translation->identity())"/>
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
    <link rel="stylesheet" href="{{ linguaAssetUrl('css/lingua.min.css') }}">
    <script type="module" src="{{ linguaAssetUrl('js/lingua.min.js') }}"></script>
@endonce
@endassets
