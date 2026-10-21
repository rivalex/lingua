<flux:modal name="{{ $modalName }}" class="lingua lingua-modal">
	<div class="flex flex-col gap-4" x-data="{ control: '' }">
        <flux:heading size="xl" level="1">
            {!! $this->deleteHeader !!}
        </flux:heading>
		<flux:separator/>
        <x-lingua::validation-errors class="text-start"/>
		<form wire:submit.prevent="deleteTranslation" id="deleteTranslation" class="flex flex-col gap-4">
			@csrf
			<p>@lang('lingua::lingua.translations.delete.alert', ['key' => $translation->key])</p>
			@if($this->isDefaultLocale)
				<p>@lang('lingua::lingua.translations.delete.alert_translations')</p>
			@endif
			<p>@lang('lingua::lingua.global.confirm.delete', ['confirm' => $confirm])</p>
            <flux:heading size="xl" level="2" class="text-red-500 dark:text-red-400">
                @lang('lingua::lingua.global.confirm.irreversible_action')
            </flux:heading>
			<flux:input type="text" x-model="control" wire:model.blur="control" required
			            :placeholder="__('lingua::lingua.global.confirm_placeholder', ['confirm' => $confirm])"/>
			<flux:separator/>
			<div class="flex justify-between gap-2 items-center">
				<flux:button variant="filled" color="gray" icon="x-mark" wire:click="close">
					@lang('lingua::lingua.global.close')
				</flux:button>
				<flux:button x-bind:type="control !== $wire.confirm ? 'button' : 'submit'" variant="danger" icon="check"
				             x-bind:disabled="control !== $wire.confirm">
					<p>{!! $this->deleteAction !!}</p>
				</flux:button>
			</div>
		</form>
	</div>
</flux:modal>
