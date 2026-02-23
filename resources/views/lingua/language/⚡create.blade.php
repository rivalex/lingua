<?php

use LaravelLang\Locales\Facades\Locales;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\Traits\Modals;

new class extends Component {

	use Modals;

	public array $availableLanguages = [];
	#[Validate('required|string')]
	public string $language = '';

	public function mount(): void
	{
		$this->modalName = 'language-create-modal';
		$this->setDefaults();
	}

	#[On('refreshLanguages')]
	public function setDefaults(): void
	{
		$this->reset('availableLanguages');
		foreach (Locales::raw()->notInstalled() as $locale) {
			try {
				$lang = Locales::info($locale);
			} catch (\Throwable) {
				continue;
			}
			$this->availableLanguages[] = [
				'code' => $lang->code,
				'label' => $lang->locale->name,
				'description' => $lang->native
			];
		}
	}

	public function addNewLanguage(): void
	{
		$this->validate();
		try {
			$newLanguage = Locales::info($this->language);
			Artisan::call('lang:add ' . $this->language);
			Language::create([
				'code' => $newLanguage->code,
				'regional' => $newLanguage->regional,
				'type' => $newLanguage->type,
				'name' => $newLanguage->locale->name,
				'native' => $newLanguage->native,
				'direction' => $newLanguage->direction,
				'is_default' => false
			]);
			Translation::syncToDatabase();
			$this->dispatch('refreshLanguages');
			$this->dispatch('language_added');
			$this->reset('language');
			$this->closeModal();
		} catch (\Throwable $e) {
			$this->reset('language');
			$this->closeModal();
			$this->dispatch('language_added_fail');
			Log::error('Languages reorder failed! {error}', ['error' => $e->getMessage()]);
		}
	}
};
?>

<div>
	<flux:modal.trigger name="{{ $modalName }}">
		<flux:button variant="primary" color="green"
		             icon="plus">@lang('rivalex::lingua.languages.create.action')</flux:button>
	</flux:modal.trigger>
	<flux:modal name="{{ $modalName }}" class="translate-modal">
		<div class="flex flex-col gap-4">
			<h2 class="text-lg">@lang('rivalex::lingua.languages.create.header')</h2>
			<form wire:submit.prevent="addNewLanguage" id="addNewLanguageForm" class="flex flex-col gap-4">
				@csrf
				<flux:select required wire:model.live="language"
				             :variant="Flux::pro() ? 'listbox' : null"
				             :searchable="Flux::pro()"
				             :clearable="Flux::pro()"
				             id="new_language" :placeholder="__('rivalex::lingua.languages.create.placeholder')"
				             label="New Language">
					@foreach($availableLanguages as $lang)
						<flux:select.option :value="$lang['code']" wire:key="oprion_{{ $lang['code'] }}">
							<x-lingua::language-flag :size="10" :code="$lang['code']" :name="$lang['label']"
							                            :description="$lang['description']"/>
						</flux:select.option>
					@endforeach
				</flux:select>
				<flux:separator/>
				<div class="flex justify-between gap-2 items-center">
					<flux:button variant="filled" color="gray" icon="x-mark"
					             x-on:click="$flux.modal('{{ $modalName }}').close()">@lang('rivalex::lingua.global.close')</flux:button>
					<flux:button type="submit" variant="primary" color="green"
					             icon="check">@lang('rivalex::lingua.global.save')</flux:button>
				</div>
			</form>
		</div>
	</flux:modal>
</div>
