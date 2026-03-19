<flux:dropdown wire:ignore>
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
    @island(name: 'languageSelectorMenu', always: true)
    <flux:menu>
        @foreach($this->languages as $locale)
            <flux:menu.item wire:click.prevent.stop="changeLocale('{{ $locale->code }}')"
                            :key="'menu_locale_' . $locale->code"
                @class(['bg-zinc-100 dark:bg-zinc-800' => $locale->code === app()->currentLocale()])>
                <div class="w-full justify-between flex items-center cursor-pointer">
                    <div class="flex flex-col grow leading-5 truncate">
                        <div class="truncate">{{ $locale->name }}</div>
                        <div class="text-xs font-light text-zinc-500 truncate italic">{{ $locale->native }}</div>
                    </div>
                    <livewire:lingua::selector.icon :locale="linguaLanguageCode($locale->code)" size="md"
                                                    :show-flags="$showFlags"/>
                </div>
            </flux:menu.item>
        @endforeach
    </flux:menu>
    @endisland
</flux:dropdown>
