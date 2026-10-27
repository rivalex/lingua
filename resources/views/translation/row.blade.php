<flux:table.row>
    <flux:table.cell class="align-top py-3 px-4 [tr:nth-child(even)_&]:bg-zinc-50 dark:[tr:nth-child(even)_&]:bg-white/[4%]">
        <div class="flex flex-col gap-1.5">
            {{-- Type badge (DB-mode only) --}}
            @if(!$fileMode)
            <div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                <x-lingua::type-icon :type="$translation->type" size="4"/>
                <p class="whitespace-normal break-all">{{ $translation->type->label() }}</p>
            </div>
            @endif

            {{-- Vendor accent bar + vendor name (vendor rows only) --}}
            @if($translation->is_vendor)
                <flux:separator class="min-h-[4px] rounded-[2px] bg-green-600! dark:bg-green-500!"/>
                <div class="flex items-center gap-2">
                    <flux:tooltip content="Vendor package" position="top">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             class="lucide lucide-box shrink-0 text-green-600 dark:text-green-500">
                            <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                            <path d="m3.3 7 8.7 5 8.7-5"/>
                            <path d="M12 22V12"/>
                        </svg>
                    </flux:tooltip>
                    <x-lingua::clipboard text-to-copy="{{ $translation->vendor }}">
                        <p class="whitespace-normal break-all text-xs text-green-600 dark:text-green-500">{{ \Illuminate\Support\Str::headline($translation->vendor) }}</p>
                    </x-lingua::clipboard>
                </div>
            @endif

            {{-- Group --}}
            <div class="flex items-center gap-2">
                <flux:tooltip content="Translation group" position="top">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         class="lucide lucide-group shrink-0 text-zinc-400 dark:text-zinc-500">
                        <path d="M3 7V5c0-1.1.9-2 2-2h2"/>
                        <path d="M17 3h2c1.1 0 2 .9 2 2v2"/>
                        <path d="M21 17v2c0 1.1-.9 2-2 2h-2"/>
                        <path d="M7 21H5c-1.1 0-2-.9-2-2v-2"/>
                        <rect width="7" height="5" x="7" y="7" rx="1"/>
                        <rect width="7" height="5" x="10" y="12" rx="1"/>
                    </svg>
                </flux:tooltip>
                <x-lingua::clipboard text-to-copy="{{ $translation->group }}">
                    <p class="whitespace-normal break-all text-xs text-zinc-500 dark:text-zinc-400">{{ $translation->group }}</p>
                </x-lingua::clipboard>
            </div>

            {{-- Key - primary element, visually prominent --}}
            <div class="flex items-center gap-2">
                <flux:tooltip content="Translation key" position="top">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         class="lucide lucide-key-round shrink-0 text-zinc-400 dark:text-zinc-500">
                        <path d="M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z"/>
                        <circle cx="16.5" cy="7.5" r=".5" fill="currentColor"/>
                    </svg>
                </flux:tooltip>
                <x-lingua::clipboard text-to-copy="{{ $translation->lang_key }}">
                    <p class="whitespace-normal break-all font-medium text-zinc-900 dark:text-white text-sm">{{ $translation->key }}</p>
                </x-lingua::clipboard>
            </div>
        </div>
    </flux:table.cell>

    <flux:table.cell class="align-top py-3 px-4 [tr:nth-child(even)_&]:bg-zinc-50 dark:[tr:nth-child(even)_&]:bg-white/[4%]">
        <div class="m-0 flex h-auto w-full max-w-full place-items-start justify-between p-0 [&_p]:m-0 [&_ul]:mb-4 [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:mb-4 [&_ol]:list-decimal [&_ol]:pl-6 [&_strong]:font-semibold [&_b]:font-semibold [&_em]:italic [&_u]:underline [&_sub]:align-sub [&_sub]:text-[length:smaller] [&_sup]:align-super [&_sup]:text-[length:smaller] [&_a]:text-accent-content [&_h1]:mb-2 [&_h1]:text-[1.5rem] [&_h1]:font-semibold [&_h2]:text-[1.25rem] [&_h2]:font-semibold [&_h3]:text-[1rem] [&_h4]:text-[0.875rem] [&_h5]:text-[0.8rem] [&_h6]:text-[0.75rem] [&_hr]:border [&_hr]:border-zinc-200 [&_blockquote]:border-l-2 [&_blockquote]:border-zinc-200 [&_blockquote]:pl-2 [&_pre:not(.markdown)]:my-2 [&_pre:not(.markdown)]:rounded-md [&_pre:not(.markdown)]:bg-zinc-100 [&_pre:not(.markdown)]:p-2 dark:[&_pre:not(.markdown)]:bg-white/10 [&_code]:px-1 [&_code]:py-0.5">
            <x-lingua::clipboard text-to-copy="{{ $defaultValue }}">
                @if($fileMode)
                    <div class="w-full whitespace-break-spaces text-[0.8rem] outline-none">{{ $defaultValue }}</div>
                @elseif($translation->type->value === 'markdown')
                    <div x-data="{ showMarkdown: @js($defaultValue) }">
                        <pre class="markdown whitespace-break-spaces text-[0.8rem]" x-text="showMarkdown"></pre>
                    </div>
                @elseif($translation->type->value === 'html')
                    <div class="w-full whitespace-break-spaces text-[0.8rem] outline-none">{!! $defaultValue !!}</div>
                @else
                    <div class="w-full whitespace-break-spaces text-[0.8rem] outline-none">{{ $defaultValue }}</div>
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
    <flux:table.cell class="align-top py-3 px-4 [tr:nth-child(even)_&]:bg-zinc-50 dark:[tr:nth-child(even)_&]:bg-white/[4%]">
        @if($fileMode)
            <flux:textarea wire:model.blur.live="value"
                           :placeholder="__('lingua::lingua.translations.fields.text')"/>
        @else
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
        @endif
    </flux:table.cell>

    <flux:table.cell align="center" class="place-items-center align-top py-3 px-4 [tr:nth-child(even)_&]:bg-zinc-50 dark:[tr:nth-child(even)_&]:bg-white/[4%]" wire:loading.class="pointer-events-none">
{{--        @if(!config('lingua.suppress_pro_nudge', false) && $currentLocale !== linguaDefaultLocale())--}}
{{--            <flux:tooltip :content="__('lingua::lingua.pro.auto_translate_tooltip')" position="top">--}}
{{--                <a href="{{ config('lingua.pro_upgrade_url', 'https://lingua.rivalex.dev') }}" target="_blank" rel="noopener noreferrer" tabindex="-1">--}}
{{--                    <flux:button tabindex="-1" variant="ghost" size="sm" icon="sparkles" disabled>--}}
{{--                        @lang('lingua::lingua.pro.auto_translate')--}}
{{--                    </flux:button>--}}
{{--                </a>--}}
{{--            </flux:tooltip>--}}
{{--        @endif--}}
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
                :translation-identity="$translation->identity()" :$currentLocale
                wire:key="update-translation-{{ md5($translation->identity()) }}"
                :modal-name="$editModalName"/>
        @endif
        @if(!$translation->is_vendor)
            <livewire:lingua::translation.delete
                :translation-identity="$translation->identity()" :$currentLocale
                wire:key="delete-translation-{{ md5($translation->identity()) }}"
                :modal-name="$deleteModalName"/>
        @endif
    </flux:table.cell>
</flux:table.row>
