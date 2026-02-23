<?php

use Illuminate\Support\Str;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Traits\ModalsConfirm;
use Livewire\Component;

new class extends Component {
	use ModalsConfirm;

	public Language $language;
	public bool $canSetDefault = false;

	public function mount(): void
	{
		$this->modalName = 'language-set-default-modal-' . $this->language->code;
		$this->confirm = Str::of(__('lingua::lingua.languages.default.confirm',
			['language' => $this->language->name]))
		                    ->upper()->squish()->trim();
	}

	public function setDefaultLanguage(): void
	{
		try {
			Language::setDefault($this->language);
			$this->dispatch('refreshLanguageRows');
			$this->dispatch('language_default_set');
			$this->closeModal();
		} catch (\Throwable $e) {
			$this->closeModal();
			$this->dispatch('language_default_fail');
			Log::error('Languages reorder failed! {error}', ['error' => $e->getMessage()]);
		}
	}
};
?>

<div wire:ignore>
	<flux:modal.trigger name="{{ $modalName }}">
		<flux:button icon="star" variant="primary" color="emerald"
		             size="xs">@lang('lingua::lingua.languages.default.button')</flux:button>
	</flux:modal.trigger>
	<flux:modal name="{{ $modalName }}" class="lingua-modal">
		<div class="flex flex-col gap-4" x-data="{ control: '' }">
			<h2 style="font-size: 1.5rem">@lang('lingua::lingua.languages.default.header', ['language' => $language->name])</h2>
			<flux:separator/>
			<form wire:submit.prevent="setDefaultLanguage" id="setDefaultLanguage" class="flex flex-col gap-4">
				@csrf
				<p style="font-size: 0.9rem">@lang('lingua::lingua.languages.default.alert', ['language' => $language->name])</p>
				<p style="font-size: 0.9rem">@lang('lingua::lingua.global.confirm.change', ['confirm' => $confirm])</p>
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
						@lang('lingua::lingua.languages.default.action', ['language' => $language->name])
					</flux:button>
				</div>
			</form>
		</div>
	</flux:modal>
</div>
