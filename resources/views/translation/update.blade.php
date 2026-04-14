<flux:modal name="{{ $modalName }}" class="lingua lingua-modal text-start">
    <div class="flex flex-col gap-4">
        <flux:heading size="xl" level="1">
            @lang('lingua::lingua.translations.update.header')
        </flux:heading>
        <flux:separator/>
        <form wire:submit.prevent="updateTranslation" id="updateTranslation" class="flex flex-col gap-4">
            @csrf
            <div class="grid grid-cols-3 gap-2">
                <div class="col-span-2">
                    <x-lingua::autocomplete wire:model.blur.live="group" required :options="$groups" :disabled="$locked"
                                            :label="__('lingua::lingua.translations.fields.group')"
                                            :placeholder="__('lingua::lingua.translations.fields.group_placeholder')"/>
                </div>
                <div class="col-span-1">
                    <flux:select wire:model.change.live="translationType" required :disabled="$locked"
                                 :badge="__('lingua::lingua.global.required')"
                                 :label="__('lingua::lingua.translations.fields.type')"
                                 :placeholder="__('lingua::lingua.translations.fields.type_placeholder')"
                                 :variant="Flux::pro() ? 'listbox' : null">
                        @foreach($translationsTypes as $translationTypeItem)
                            <flux:select.option value="{{ $translationTypeItem['value'] }}">
                                {!! $translationTypeItem['label'] !!}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="col-span-3">
                    <flux:input wire:model.blur.live="key" type="text" icon="key" :disabled="$locked"
                                :label="__('lingua::lingua.translations.fields.key')"
                                :placeholder="__('lingua::lingua.translations.fields.key_placeholder')"
                                required :badge="__('lingua::lingua.global.required')"/>
                </div>
                <div class="col-span-3">
                    <div x-cloak x-show="$wire.translationType === 'text'">
                        <flux:textarea wire:key="editor-text" wire:model.blur="textValue" rows="3" :helper="false"
                                       :required="$required"
                                       :label="__('lingua::lingua.translations.fields.textValue')"
                                       :placeholder="__('lingua::lingua.translations.fields.text')"/>
                    </div>
                    <div x-cloak x-show="$wire.translationType === 'html'">
                        <x-lingua::editor wire:model.blur="htmlValue" type="html" :helper="false"
                                             :required="$required"
                                             :label="__('lingua::lingua.translations.fields.htmlValue')"
                                             :placeholder="__('lingua::lingua.translations.fields.html')"/>
                    </div>
                    <div x-cloak x-show="$wire.translationType === 'markdown'">
                        <x-lingua::editor wire:model.blur="mdValue" type="markdown" :helper="false"
                                             :required="$required"
                                             :label="__('lingua::lingua.translations.fields.mdValue')"
                                             :placeholder="__('lingua::lingua.translations.fields.md')"/>
                    </div>
                </div>
            </div>
            <flux:separator/>
            <div class="flex justify-between gap-2 items-center">
                <flux:button variant="filled" color="gray" icon="x-mark"
                             x-on:click="$flux.modal('{{ $modalName }}').close()">@lang('lingua::lingua.global.close')</flux:button>
                <flux:button type="submit" variant="primary" color="green"
                             icon="check">@lang('lingua::lingua.global.save')</flux:button>
            </div>
        </form>
    </div>
</flux:modal>
