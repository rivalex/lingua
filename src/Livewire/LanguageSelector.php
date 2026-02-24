<?php

namespace Rivalex\Lingua\Livewire;

use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Traits\Modals;

class LanguageSelector extends Component
{
    use Modals;

    public
    bool $modal = false;
    public string $currentUrl = '';

    public function mount(): void
    {
        $this->modalName = 'language-selector-modal';
        $this->currentUrl = url()->current();
    }

    #[On('refreshLanguages')]
    public function refreshLanguagesSelector(): void
    {
        if($this->modal) {
            $this->renderIsland('languageSelectorModal');
        } else {
            $this->renderIsland('languageSelectorMenu');
        }
    }

    #[Computed]
    public function languages()
    {
        return Language::query()->active()->get();
    }

    public function changeLocale($locale): void
    {
        Session::put(config('lingua.session_variable'), $locale);
        app()->setLocale($locale);
        $this->redirect(url: $this->currentUrl, navigate: true);
    }

	public function render()
	{
		return view('lingua::language_selector');
	}
}
