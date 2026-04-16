<div wire:ignore>
    <flux:modal.trigger name="{{ $modalName }}">
        <flux:button variant="primary" color="green"
                     icon="plus">@lang('lingua::lingua.languages.create.action')</flux:button>
    </flux:modal.trigger>
    <flux:modal name="{{ $modalName }}" class="lingua lingua-modal">
        <div class="flex flex-col gap-4">
            <flux:heading size="xl" level="1">@lang('lingua::lingua.languages.create.header')</flux:heading>
            <flux:separator/>
            <form wire:submit.prevent="addNewLanguage" id="addNewLanguageForm" class="flex flex-col gap-4">
                @csrf
                <flux:select required wire:model.live="language"
                             :variant="Flux::pro() ? 'listbox' : null"
                             :searchable="Flux::pro()"
                             :clearable="Flux::pro()"
                             id="new_language" :placeholder="__('lingua::lingua.languages.create.placeholder')"
                             label="New Language">
                    @foreach($availableLanguages as $lang)
                        <flux:select.option :value="$lang['code']" :key="'option_' . $lang['code']">
                            <x-lingua::language-flag :size="8" :code="$lang['code']" :name="$lang['label']"
                                                     :description="$lang['description']"/>
                        </flux:select.option>
                    @endforeach
                </flux:select>
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
</div>
