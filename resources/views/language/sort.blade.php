<div wire:ignore class="flex flex-col gap-4">
    @if(count($this->languages) > 1)
    <flux:separator/>
    <div class="relative w-full">
        <div class="language-sort-container">
            <div class="flex w-full justify-between items-center">
                <div class="flex flex-col gap-1">
                    <h1 class="font-bold">@lang('lingua::lingua.languages.sort.title')</h1>
                    <h3 class="text-xs">@lang('lingua::lingua.languages.sort.subtitle')</h3>
                </div>
                <x-lingua::message on="languages_sorted">
                    <flux:badge color="green">
                        <div class="flex items-center gap-2">
                            <flux:icon icon="check-circle" size="sm"/>
                            <p>@lang('lingua::lingua.languages.sort.sorted')</p>
                        </div>
                    </flux:badge>
                </x-lingua::message>
                <x-lingua::message on="languages_sorted_fail">
                    <flux:badge color="red">
                        <div class="flex items-center gap-2">
                            <flux:icon icon="exclamation-circle" size="sm"/>
                            <p>@lang('lingua::lingua.languages.sort.sorted_fail')</p>
                        </div>
                    </flux:badge>
                </x-lingua::message>
            </div>
            <flux:separator/>
            <ul wire:sort="updateLanguageOrder" class="flex flex-wrap gap-4">
                @foreach ($this->languages as $language)
                    <li wire:sort:item="{{ $language->id }}" wire:key="language-{{ $language->id }}">
                        <flux:badge class="flex gap-2">
                            <svg wire:sort:handle style="cursor: grab;" xmlns="http://www.w3.org/2000/svg" width="24"
                                 height="24" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                 class="lucide lucide-grip-vertical-icon lucide-grip-vertical">
                                <circle cx="9" cy="12" r="1"/>
                                <circle cx="9" cy="5" r="1"/>
                                <circle cx="9" cy="19" r="1"/>
                                <circle cx="15" cy="12" r="1"/>
                                <circle cx="15" cy="5" r="1"/>
                                <circle cx="15" cy="19" r="1"/>
                            </svg>
                            @svg('flag-circle-language-'.languageCode($language->code), 'w-6 h-6')
                        </flux:badge>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif
</div>
