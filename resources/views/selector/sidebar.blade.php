<div class="lingua">
    <x-lingua::menu-group expandable :expanded="false" heading="{{ __('lingua::lingua.selector.menu_title') }}" class="grid">
        <x-slot name="icon" class="p-0">
            <livewire:lingua::selector.icon locale="{{ app()->currentLocale() }}" :show-flags="$showFlags"/>
        </x-slot>
        @foreach($this->languages as $locale)
            <flux:sidebar.item wire:click.prevent.stop="changeLocale('{{ $locale->code }}')"
                               :key="'sidebar_' . $locale->code"
                               :current="app()->currentLocale() === $locale->code"
                               style="padding: 1.4rem 0.5rem !important">
                <div class="justify-between flex items-center">
                    <div class="flex flex-col grow leading-5 truncate">
                        <div class="truncate">{{ $locale->name }}</div>
                        <div class="text-xs font-light text-zinc-500 truncate italic">{{ $locale->native }}</div>
                    </div>
                    <livewire:lingua::selector.icon locale="{{ $locale->code }}" :show-flags="$showFlags"/>
                </div>
            </flux:sidebar.item>
        @endforeach
    </x-lingua::menu-group>
</div>
@assets
@once
    <link rel="stylesheet" href="{{ route('lingua.assets', 'css/lingua.min.css') }}">
@endonce
@endassets
