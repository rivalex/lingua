<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\On;
use Livewire\Component;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Traits\Modals;

new class extends Component {

    use Modals;

    public
    bool $modal = false;
    public string $currentUrl = '';

    public function mount(): void
    {
        $this->modalName = 'language-selector-modal';
        $this->currentUrl = url()->current();
    }

    #[On('refreshLanguages')]
    public function refreshLanguagesSelector(): void
    {
        $this->renderIsland('languageItems');
    }

    #[Computed]
    public function languages()
    {
        return Language::query()->active()->get();
    }

    public function changeLocale($locale): void
    {
        Session::put(config('lingua.session_variable'), $locale);
        app()->setLocale($locale);
        $this->redirect(url: $this->currentUrl, navigate: true);
    }
};
?>

<div>
    @if($modal)
        <flux:modal.trigger name="{{ $modalName }}">
            <flux:button variant="filled" square>
                <x-icon name="flag-language-{{ app()->currentLocale() }}" style="width: 2rem;"/>
            </flux:button>
        </flux:modal.trigger>
        <flux:modal name="{{ $modalName }}" class="translate-modal">
            <div class="flex flex-col gap-4">
                <h2 class="text-lg">@lang('language.select')</h2>
                @island(name: 'languageItems')
                <div class="flex flex-wrap gap-4">
                    @foreach($this->languages as $locale)
                        <flux:button type="button"
                                     wire:click.prevent.stop="changeLocale('{{ $locale->code }}')"
                                     :key="'locale_{{ $locale->code }}'"
                            @class(['bg-zinc-100' => $locale->code === app()->currentLocale()])>
                            <div class="justify-between flex items-center text-start"
                                 style="width: 8rem; min-width: 8rem; max-width: 8rem;">
                                <div class="flex flex-col grow leading-5 truncate">
                                    <div class="truncate">{{ $locale->name }}</div>
                                    <div class="text-xs font-light text-gray-500 truncate">{{ $locale->native }}</div>
                                </div>
                                <x-icon name="flag-language-{{ $locale->code }}" style="width: 2.5rem;"/>
                            </div>
                        </flux:button>
                    @endforeach
                </div>
                @endisland
            </div>
        </flux:modal>
    @else
        <flux:dropdown>
            <flux:button variant="filled" square>
                <x-icon name="flag-language-{{ app()->currentLocale() }}" style="width: 2rem;"/>
            </flux:button>
            <flux:menu>
                @island(name: 'languageItems')
                @foreach($this->languages as $locale)
                    <flux:menu.item wire:click.prevent.stop="changeLocale('{{ $locale->code }}')"
                                    :key="'locale_{{ $locale->code }}'"
                        @class(['bg-zinc-100' => $locale->code === app()->currentLocale()])>
                        <div class="w-full justify-between flex items-center">
                            <div class="flex flex-col grow leading-5 truncate">
                                <div class="truncate">{{ $locale->name }}</div>
                                <div class="text-xs font-light text-gray-500 truncate">{{ $locale->native }}</div>
                            </div>
                            <x-icon name="flag-language-{{ $locale->code }}" style="width: 2.5rem;"/>
                        </div>
                    </flux:menu.item>
                @endforeach
                @endisland
            </flux:menu>
        </flux:dropdown>
    @endif

</div>
