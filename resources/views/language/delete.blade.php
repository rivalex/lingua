<div wire:ignore>
	<flux:modal.trigger name="{{ $modalName }}">
		<flux:button variant="primary" color="red" icon="trash"/>
	</flux:modal.trigger>
	<flux:modal name="{{ $modalName }}" class="lingua-modal">
		<div class="flex flex-col gap-4" x-data="{ control: '' }">
			<h2 class="text-lg">@lang('lingua::lingua.languages.delete.header', ['language' => $language->name])</h2>
			<flux:separator/>
			<form wire:submit.prevent="deleteLanguage" id="deleteLanguageForm" class="flex flex-col gap-4">
				@csrf
				<p>@lang('lingua::lingua.languages.delete.alert', ['language' => $language->name])</p>
				<p>@lang('lingua::lingua.languages.delete.alert_translations', ['language' => $language->name])</p>
				<p>@lang('lingua::lingua.global.confirm.delete', ['confirm' => $confirm])</p>
				<strong
						class="text-red-500 text-lg">@lang('lingua::lingua.global.confirm.irreversible_action')</strong>
				<flux:input type="text" x-model="control" wire:model.blur="control" required
				            :placeholder="__('lingua::lingua.global.confirm_placeholder', ['confirm' => $confirm])"/>
				<flux:separator/>
				<div class="flex justify-between gap-2 items-center">
					<flux:button variant="filled" color="gray" icon="x-mark" wire:click="close">
						@lang('lingua::lingua.global.close')
					</flux:button>
					<flux:button x-bind:type="control !== $wire.confirm ? 'button' : 'submit'" variant="primary"
					             color="red" icon="check"
					             x-bind:disabled="control !== $wire.confirm">
						@lang('lingua::lingua.languages.delete.action', ['language' => $language->name])
					</flux:button>
				</div>
			</form>
		</div>
	</flux:modal>
</div>
