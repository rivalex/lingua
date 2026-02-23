<?php

use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\Traits\ModalsConfirm;
use Rivalex\Lingua\Traits\NotificationService;
use Livewire\Component;

new class extends Component {
	use ModalsConfirm;
	use NotificationService;

	public string $currentLocale;
	public string $localName;
	public Translation $translation;
	public bool $canDelete = false;
	public string $deleteAction;
	public string $deleteHeader;

	public bool $isDefaultLocale;

	public function mount(): void
	{
		$this->isDefaultLocale = $this->currentLocale === defaultLocale();
		$this->localName = Language::where('code', $this->currentLocale)->first()->native;
		if ($this->isDefaultLocale) {
			$this->deleteHeader = __('translations.delete.header');
			$this->confirm = __('translations.delete.confirm');
			$this->deleteAction = __('translations.delete.action');
		} else {
			$this->deleteHeader = __('translations.delete.header_locale', ['locale' => strtoupper($this->localName)]);
			$this->confirm = __('translations.delete.confirm_locale', ['locale' => strtoupper($this->localName)]);
			$this->deleteAction = __('translations.delete.action_translation_locale', ['locale' => $this->localName]);
		}
	}

	public function deleteTranslation(): void
	{
		try {
			$this->closeModal();
			if ($this->isDefaultLocale) {
				$this->translation->delete();
				$this->dispatch('refreshTranslationsTable');
				$this->dispatch('refreshTranslationsTable');
				$this->success(
					title: 'Translation deleted!',
					description: 'The translation has been deleted successfully.'
				);
			} else {
				$this->translation->forgetTranslation($this->currentLocale);
				$this->dispatch('refreshTranslationRow.' . $this->translation->id);
				$this->success(
					title: $this->localName . ' Translation deleted!',
					description: 'The ' . $this->localName . ' translation has been deleted successfully.'
				);
			}
		} catch (\Exception $e) {
			$this->closeModal();
			$this->error(
				title: 'Translation delete failed!',
				description: 'An error occurred while trying to delete the translation.<br>Error: ' . $e->getMessage()
			);
		}
	}
};
?>

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

