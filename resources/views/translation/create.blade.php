<div wire:ignore>
    <flux:modal.trigger name="{{ $modalName }}">
        <flux:button variant="primary" color="green"
                     icon="plus">@lang('lingua::lingua.translations.create.action')</flux:button>
    </flux:modal.trigger>
    <flux:modal name="{{ $modalName }}" class="lingua lingua-modal">
        <div class="flex flex-col gap-4">
            <flux:heading size="xl" level="1">
                @lang('lingua::lingua.translations.create.header')
            </flux:heading>
            <flux:separator/>
            <x-lingua::validation-errors class="text-start"/>
            <form wire:submit.prevent="addNewTranslation" id="addNewTranslationForm" class="flex flex-col gap-4">
                @csrf
                <div class="grid grid-cols-3 gap-2 items-start">
                    <div class="col-span-2">
                        <x-lingua::autocomplete wire:model.blur.live="group" required :options="$groups"
                                                :label="__('lingua::lingua.translations.fields.group')"
                                                :placeholder="__('lingua::lingua.translations.fields.group_placeholder')"/>
                    </div>
                    <div class="col-span-1">
                        <flux:select wire:model.change.live="translationType"
                                     required
                                     :badge="__('lingua::lingua.global.required')"
                                     :label="__('lingua::lingua.translations.fields.type')"
                                     :placeholder="__('lingua::lingua.translations.fields.type_placeholder')"
                                     :variant="Flux::pro() ? 'listbox' : null">
                            @foreach($translationsTypes as $translationTypeItem)
                                <flux:select.option value="{{ $translationTypeItem['value'] }}"
                                                    :key="'group-' . $translationTypeItem['value']">
                                    {!! $translationTypeItem['label'] !!}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                    <div class="col-span-3">
                        <flux:input wire:model.blur.live="key" type="text" icon="key"
                                    :label="__('lingua::lingua.translations.fields.key')"
                                    :placeholder="__('lingua::lingua.translations.fields.key_placeholder')"
                                    required :badge="__('lingua::lingua.global.required')"/>
                    </div>
                    <div class="col-span-3">
                        <div x-cloak x-show="$wire.translationType === 'text'">
                            <x-lingua::editor wire:model.blur="textValue" type="text" :helper="false"
                                              :label="__('lingua::lingua.translations.fields.textValue')"
                                              :placeholder="__('lingua::lingua.translations.fields.text')"/>
                        </div>
                        <div x-cloak x-show="$wire.translationType === 'html'">
                            <x-lingua::editor wire:model.blur="htmlValue" type="html" :helper="false"
                                              :label="__('lingua::lingua.translations.fields.htmlValue')"
                                              :placeholder="__('lingua::lingua.translations.fields.html')"/>
                        </div>
                        <div x-cloak x-show="$wire.translationType === 'markdown'">
                            <x-lingua::editor wire:model.blur="mdValue" type="markdown" :helper="false"
                                              :label="__('lingua::lingua.translations.fields.mdValue')"
                                              :placeholder="__('lingua::lingua.translations.fields.md')"/>
                        </div>
                    </div>
                </div>
                <flux:separator/>
                {{--                @if(!config('lingua.suppress_pro_nudge', false))--}}
                {{--                <div class="flex items-center justify-between gap-3 rounded-md border border-violet-100 bg-violet-50 px-3 py-2 text-xs dark:border-violet-800 dark:bg-violet-950">--}}
                {{--                    <div class="flex items-center gap-2 text-violet-600 dark:text-violet-300">--}}
                {{--                        <flux:icon.sparkles size="xs" class="shrink-0"/>--}}
                {{--                        <span>@lang('lingua::lingua.pro.hint_text')</span>--}}
                {{--                    </div>--}}
                {{--                    <a--}}
                {{--                        href="{{ config('lingua.pro_upgrade_url', 'https://lingua.rivalex.com') }}"--}}
                {{--                        target="_blank"--}}
                {{--                        rel="noopener noreferrer"--}}
                {{--                        class="shrink-0 font-semibold text-violet-600 hover:underline dark:text-violet-400"--}}
                {{--                    >@lang('lingua::lingua.pro.hint_cta')</a>--}}
                {{--                </div>--}}
                {{--                @endif--}}
                <div class="flex justify-between gap-2 items-center">
                    <flux:button variant="filled" color="gray" icon="x-mark"
                                 x-on:click="$flux.modal('{{ $modalName }}').close()">@lang('lingua::lingua.global.close')</flux:button>
                    <flux:button type="submit" variant="primary" color="green"
                                 icon="check">@lang('lingua::lingua.global.save')</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>

