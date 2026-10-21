<?php

namespace Rivalex\Lingua\Livewire;

use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Rivalex\Lingua\Facades\Lingua;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Traits\Modals;

class LanguageSelector extends Component
{
    use Modals;

    public bool $modal = false;

    public string $mode = ''; // modal | sidebar | dropdown

    public bool $showFlags = true;

    public string $currentLocale = '';

    public string $currentUrl = '';

    public function mount($mode = null, $showFlags = null): void
    {
        $this->mode = $mode ?? config('lingua.selector.mode');
        $this->showFlags = ($showFlags !== null) ? (bool) $showFlags : config('lingua.selector.show_flags' ?? true);
        $this->modalName = 'language-selector-modal';
        $this->currentLocale = app()->currentLocale();
        $this->currentUrl = url()->current();
    }

    #[On('refreshLanguages')]
    public function refreshLanguagesSelector(): void
    {
        if ($this->modal) {
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
        if (! Lingua::hasLocale($locale)) {
            return;
        }
        Session::put(config('lingua.session_variable'), $locale);
        app()->setLocale($locale);
        $this->redirect(url: $this->currentUrl, navigate: true);
    }

    public function render()
    {
        return match ($this->mode) {
            'modal' => view('lingua::selector.modal'),
            'dropdown' => view('lingua::selector.dropdown'),
            default => view('lingua::selector.sidebar'),
        };
    }
}
