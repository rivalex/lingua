<div>
    <flux:modal.trigger name="{{ $modalName }}">
        <flux:button variant="filled" square class="cursor-pointer">
            <div class="flex flex-col items-center w-full px-1.5 space-y-[2px]">
                <flux:icon.language class="w-4 h-4"/>
                <flux:separator/>
                @if($showFlags)
                    @svg('flag-language-'.linguaLanguageCode(app()->currentLocale()), 'w-4 h-4')
                @else
                    <p class="text-xs font-light uppercase">{{ app()->currentLocale() }}</p>
                @endif
            </div>
        </flux:button>
    </flux:modal.trigger>
    <flux:modal name="{{ $modalName }}" class="lingua-modal">
        <div class="flex flex-col gap-4">
            <flux:heading size="lg" level="1">
                @lang('lingua::lingua.selector.modal_header')
            </flux:heading>
            <flux:separator/>
            @island(name: 'languageSelectorModal', always: true)
            <div class="flex flex-wrap gap-4">
                @foreach($this->languages as $locale)
                    <flux:button type="button" :key="'modal_locale_' . $locale->code"
                                 wire:click.prevent.stop="changeLocale('{{ $locale->code }}')"
                        @class(['bg-zinc-100' => $locale->code === app()->currentLocale()])>
                        <div class="justify-between flex items-center text-start"
                             style="width: 8rem; min-width: 8rem; max-width: 8rem;">
                            <div class="flex flex-col grow leading-5 truncate">
                                <div class="truncate">{{ $locale->name }}</div>
                                <div class="text-xs font-light text-gray-500 truncate">{{ $locale->native }}</div>
                            </div>
                            <livewire:lingua::selector.icon :locale="linguaLanguageCode($locale->code)" size="md"
                                                            :show-flags="$showFlags"/>
                        </div>
                    </flux:button>
                @endforeach
            </div>
            @endisland
        </div>
    </flux:modal>
</div>
