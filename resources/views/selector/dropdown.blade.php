<div class="lingua">
    <div x-data="{ open: false }" class="relative">
        <flux:button variant="filled" square @click="open = !open" class="cursor-pointer">
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
        <div x-show="open"
             x-cloak
             @click.outside="open = false"
             class="absolute right-0 top-full z-50 mt-1 w-56 overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-800">
            @foreach($this->languages as $locale)
                <button
                    type="button"
                    wire:click.prevent.stop="changeLocale('{{ $locale->code }}')"
                    @click="open = false"
                    @class([
                        'w-full flex items-center px-3 py-2 text-left text-sm transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-700',
                        'bg-zinc-100 dark:bg-zinc-800' => $locale->code === app()->currentLocale(),
                    ])>
                    <div class="flex w-full items-center justify-between">
                        <div class="flex grow flex-col truncate leading-5">
                            <div class="truncate">{{ $locale->name }}</div>
                            <div class="truncate text-xs font-light italic text-zinc-500">{{ $locale->native }}</div>
                        </div>
                        <livewire:lingua::selector.icon :locale="$locale->code" size="md"
                                                        :show-flags="$showFlags"/>
                    </div>
                </button>
            @endforeach
        </div>
        @endisland
    </div>
</div>
@assets
@once
    <link rel="stylesheet" href="{{ route('lingua.assets', 'css/lingua.min.css') }}">
@endonce
@endassets
