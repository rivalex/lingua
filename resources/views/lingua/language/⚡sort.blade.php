<?php

use Livewire\Attributes\Async;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Rivalex\Lingua\Models\Language;

new class extends Component {

	#[Renderless, Async]
	public function updateLanguageOrder($item, $position): void
	{
		try {
			$languages = Language::orderBy('sort')->get();
			$movedLanguage = $languages->firstWhere('id', $item);
			if (!$movedLanguage) {
				return;
			}
			$languages = $languages->except($item);
			$languages->splice($position, 0, [$movedLanguage]);
			$languages->each(function ($language, $index) {
				$language->update(['sort' => $index + 1]);
			});
			$this->dispatch('languages_sorted');
			$this->dispatch('refreshLanguages');
			$this->dispatch('refreshLanguageSelector');
		} catch (Throwable $e) {
			$this->dispatch('languages_sorted_fail');
			Log::error('Languages reorder failed! {error}', ['error' => $e->getMessage()]);
		}
	}

	#[Computed]
	public function languages()
	{
		return Language::orderBy('sort')->get();
	}

	#[On('refreshLanguages')]
	public function refreshSortList(): void
	{
		$this->forceRender();
	}
};
?>

<div class="language-sort-container">
	<div class="flex w-full justify-between items-center">
		<div class="flex flex-col gap-1">
			<h1 class="font-bold">@lang('rivalex::lingua.languages.sort.title')</h1>
			<h3 class="text-xs">@lang('rivalex::lingua.languages.sort.subtitle')</h3>
		</div>
		<x-lingua::action-message on="languages_sorted">
			<flux:badge color="green">
				<div class="flex items-center gap-2">
					<flux:icon icon="check-circle" size="sm"/>
					<p>@lang('rivalex::lingua.languages.sort.sorted')</p>
				</div>
			</flux:badge>
		</x-lingua::action-message>
		<x-lingua::action-message on="languages_sorted_fail">
			<flux:badge color="red">
				<div class="flex items-center gap-2">
					<flux:icon icon="exclamation-circle" size="sm"/>
					<p>@lang('rivalex::lingua.languages.sort.sorted_fail')</p>
				</div>
			</flux:badge>
		</x-lingua::action-message>
	</div>
	<flux:separator/>
	<ul wire:sort="updateLanguageOrder" class="flex flex-wrap gap-4">
		@foreach ($this->languages as $language)
			<li wire:sort:item="{{ $language->id }}">
				<flux:badge class="flex gap-2">
					<svg wire:sort:handle style="cursor: grab;" xmlns="http://www.w3.org/2000/svg" width="24"
					     height="24" viewBox="0 0 24 24" fill="none"
					     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
					     class="lucide lucide-grip-vertical-icon lucide-grip-vertical">
						<circle cx="9" cy="12" r="1"/>
						<circle cx="9" cy="5" r="1"/>
						<circle cx="9" cy="19" r="1"/>
						<circle cx="15" cy="12" r="1"/>
						<circle cx="15" cy="5" r="1"/>
						<circle cx="15" cy="19" r="1"/>
					</svg>
					<x-icon name="flag-language-{{ $language->code }}" class="w-8 h-8"/>
				</flux:badge>
			</li>
		@endforeach
	</ul>
</div>
