<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Rivalex\Lingua\Models\LinguaSetting;
use Rivalex\Lingua\Traits\ManagesLocale;
use Rivalex\Lingua\Traits\Modals;

class LanguageSelector extends Component
{
    use ManagesLocale;
    use Modals;

    public bool $modal = false;

    public string $mode = ''; // modal | sidebar | dropdown

    public bool $showFlags = true;

    /**
     * Initialise selector state from DB settings, config, and request context.
     *
     * @param  string|null  $mode  Override the selector display mode.
     * @param  bool|null  $showFlags  Override whether language flags are shown.
     */
    public function mount($mode = null, $showFlags = null): void
    {
        $this->mode = $mode ?? LinguaSetting::get(LinguaSetting::KEY_SELECTOR_MODE, config('lingua.selector.mode'));
        $this->showFlags = ($showFlags !== null)
            ? (bool) $showFlags
            : (bool) LinguaSetting::get(LinguaSetting::KEY_SHOW_FLAGS, config('lingua.selector.show_flags', true));
        $this->modalName = 'language-selector-modal';
        $this->initLocaleState();
    }

    /**
     * Refresh the rendered island after a languages change event.
     */
    #[On('refreshLanguages')]
    public function refreshLanguagesSelector(): void
    {
        if ($this->modal) {
            $this->renderIsland('languageSelectorModal');
        } else {
            $this->renderIsland('languageSelectorMenu');
        }
    }

    /**
     * Render the appropriate selector view based on the active mode.
     */
    public function render(): View
    {
        return match ($this->mode) {
            'modal' => view('lingua::selector.modal'),
            'dropdown' => view('lingua::selector.dropdown'),
            default => view('lingua::selector.sidebar'),
        };
    }
}
