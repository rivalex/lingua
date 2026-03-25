<flux:table.row>
    <flux:table.cell class="lingua-row">
        <div class="flex flex-col gap-2">
            <div class="flex flex-row gap-2 items-center text-sm">
                {!! $translation->type->iconColor(6) !!}
                <p class="translation-group-key" style="font-weight: bold;">{{ $translation->type->label() }}</p>
            </div>
            <flux:separator @class(['vendor' => $translation->is_vendor])/>
            @if($translation->is_vendor)
                <div class="flex flex-row gap-2 items-center">
                    <flux:tooltip content="Vendor package" position="top">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             class="lucide lucide-box-icon lucide-box vendor_item">
                            <path
                                d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                            <path d="m3.3 7 8.7 5 8.7-5"/>
                            <path d="M12 22V12"/>
                        </svg>
                    </flux:tooltip>
                    <x-lingua::clipboard text-to-copy="{{ $translation->vendor }}">
                        <p class="translation-group-key vendor_item" style="font-weight: bold;">{{ \Illuminate\Support\Str::headline($translation->vendor) }}</p>
                    </x-lingua::clipboard>
                </div>
            @endif

            <div class="flex flex-row gap-2">
                <flux:tooltip content="Translation group" position="top">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         class="lucide lucide-group-icon lucide-group">
                        <path d="M3 7V5c0-1.1.9-2 2-2h2"/>
                        <path d="M17 3h2c1.1 0 2 .9 2 2v2"/>
                        <path d="M21 17v2c0 1.1-.9 2-2 2h-2"/>
                        <path d="M7 21H5c-1.1 0-2-.9-2-2v-2"/>
                        <rect width="7" height="5" x="7" y="7" rx="1"/>
                        <rect width="7" height="5" x="10" y="12" rx="1"/>
                    </svg>
                </flux:tooltip>
                <x-lingua::clipboard text-to-copy="{{ $translation->group }}">
                    <p class="translation-group-key" style="font-weight: bold;">{{ $translation->group }}</p>
                </x-lingua::clipboard>
            </div>

            <div class="flex flex-row gap-2">
                <flux:tooltip content="Translation key" position="top">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         class="lucide lucide-key-round-icon lucide-key-round">
                        <path
                            d="M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z"/>
                        <circle cx="16.5" cy="7.5" r=".5" fill="currentColor"/>
                    </svg>
                </flux:tooltip>
                <x-lingua::clipboard text-to-copy="{{ $translation->lang_key }}">
                    <p class="translation-group-key">{{ $translation->key }}</p>
                </x-lingua::clipboard>
            </div>
        </div>
    </flux:table.cell>

    <flux:table.cell class="lingua-row">
        <div class="lingua-preview">
            <x-lingua::clipboard text-to-copy="{{ $defaultValue }}">
                @if($translation->type->value === 'markdown')
                    <div x-data="{ showMarkdown: @js($defaultValue) }">
                        <pre class="markdown" x-text="showMarkdown"></pre>
                    </div>
                @else
                    <div class="preview">{!! $defaultValue !!}</div>
                @endif
            </x-lingua::clipboard>
            <div class="flex flex-col gap-1 items-center">
                <flux:button variant="ghost" tabindex="-1" size="sm" icon="arrow-path"
                             wire:click="syncFromDefault"></flux:button>
                <x-lingua::message on="{{ $translation->group_key }}_updated">
                    <flux:icon icon="check-circle" variant="solid"
                               class="text-green-500 dark:text-green-400"/>
                </x-lingua::message>
            </div>
        </div>
    </flux:table.cell>
    <flux:table.cell class="lingua-row">
        <div x-cloak x-show="$wire.translationType === 'text'">
            <x-lingua::editor wire:model.blur.live="value" type="text"
                              :placeholder="__('lingua::lingua.translations.fields.text')"/>
        </div>
        <div x-cloak x-show="$wire.translationType === 'html'">
            <x-lingua::editor wire:model.blur.live="value" type="html"
                              :placeholder="__('lingua::lingua.translations.fields.html')"/>
        </div>
        <div x-cloak x-show="$wire.translationType === 'markdown'">
            <x-lingua::editor wire:model.blur.live="value" type="markdown"
                              :placeholder="__('lingua::lingua.translations.fields.md')"/>
        </div>
    </flux:table.cell>

    <flux:table.cell align="center" class="lingua-row center" wire:loading.class="pointer-events-none">
        <flux:button.group>
            @if($this->currentLocale === linguaDefaultLocale())
                <flux:modal.trigger name="{{ $editModalName }}">
                    <flux:button tabindex="-1" variant="primary" color="green"
                                 icon="pencil-square"/>
                </flux:modal.trigger>
            @endif
            @if(!$translation->is_vendor)
                <flux:modal.trigger name="{{ $deleteModalName }}">
                    <flux:button tabindex="-1" variant="danger" icon="trash"></flux:button>
                </flux:modal.trigger>
            @endif
        </flux:button.group>
        @if($this->currentLocale === linguaDefaultLocale())
            <livewire:lingua::translation.update
                :$translation :$currentLocale
                wire:key="update-translation-{{ $translation->id }}"
                :modal-name="$editModalName"/>
        @endif
        @if(!$translation->is_vendor)
            <livewire:lingua::translation.delete
                :$translation :$currentLocale
                wire:key="delete-translation-{{ $translation->id }}"
                :modal-name="$deleteModalName"/>
        @endif
    </flux:table.cell>
</flux:table.row>
