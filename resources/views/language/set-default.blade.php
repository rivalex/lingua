<div wire:ignore>
    <flux:modal.trigger name="{{ $modalName }}">
        <flux:button icon="star" variant="primary" color="emerald"
                     size="xs">@lang('lingua::lingua.languages.default.button')</flux:button>
    </flux:modal.trigger>
    <flux:modal name="{{ $modalName }}" class="lingua w-11/12 min-[992px]:w-[42rem]">
        <div class="flex flex-col gap-4" x-data="{ control: '' }">
            <flux:heading size="xl" level="1">
                @lang('lingua::lingua.languages.default.header', ['language' => e($language->name)])
            </flux:heading>
            <flux:separator/>
            <form wire:submit.prevent="setDefaultLanguage" id="setDefaultLanguage" class="flex flex-col gap-4">
                @csrf
                <p class="text-[0.9rem]">@lang('lingua::lingua.languages.default.alert', ['language' => e($language->name)])</p>
                <p class="text-[0.9rem]">@lang('lingua::lingua.global.confirm.change', ['confirm' => e($confirm)])</p>
                <flux:input type="text" x-model="control" wire:model.blur="control" required
                            :placeholder="__('lingua::lingua.global.confirm_placeholder', ['confirm' => $confirm])"/>
                <flux:separator/>
                <div class="flex justify-between gap-2 items-center">
                    <flux:button variant="filled" color="gray" icon="x-mark" wire:click="close">
                        @lang('lingua::lingua.global.close')
                    </flux:button>
                    <flux:button x-bind:type="control !== $wire.confirm ? 'button' : 'submit'"
                                 x-bind:disabled="control !== $wire.confirm"
                                 variant="primary" color="emerald" icon="star">
                        @lang('lingua::lingua.languages.default.action', ['language' => e($language->name)])
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
