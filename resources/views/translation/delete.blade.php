<flux:modal name="{{ $modalName }}" class="lingua-modal">
	<div class="flex flex-col gap-4" x-data="{ control: '' }">
		<h2 class="text-xl">{!! $this->deleteHeader !!}</h2>
		<flux:separator/>
		<form wire:submit.prevent="deleteTranslation" id="deleteTranslation" class="flex flex-col gap-4">
			@csrf
			<p>@lang('rivalex::translations.delete.alert', ['key' => $translation->key])</p>
			@if($this->isDefaultLocale)
				<p>@lang('rivalex::translations.delete.alert_translations')</p>
			@endif
			<p>@lang('rivalex::language.delete.confirm', ['confirm' => $confirm])</p>
			<p class="text-red-500 dark:text-red-400 font-black text-xl">@lang('rivalex::language.global.confirm.irreversible_action')</p>
			<flux:input type="text" x-model="control" wire:model.blur="control" required
			            :placeholder="__('lingua::language.global.confirm_placeholder', ['confirm' => $confirm])"/>
			<flux:separator/>
			<div class="flex justify-between gap-2 items-center">
				<flux:button variant="filled" color="gray" icon="x-mark" wire:click="close">
					@lang('rivalex::language.global.close')
				</flux:button>
				<flux:button x-bind:type="control !== $wire.confirm ? 'button' : 'submit'" variant="danger" icon="check"
				             x-bind:disabled="control !== $wire.confirm">
					<p>{!! $this->deleteAction !!}</p>
				</flux:button>
			</div>
		</form>
	</div>
</flux:modal>

