<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Rivalex\Lingua\Traits\ManagesLocale;

/**
 * A headless language selector Livewire component.
 *
 * Renders zero CSS and no framework-specific markup — just semantic HTML
 * with data-lingua-* attributes that consumers can target with their own styles.
 *
 * Usage: <livewire:lingua::headless-language-selector />
 *
 * The language list is always visible in the DOM; toggle visibility externally
 * via CSS or your own JavaScript. No $trigger slot is provided by design.
 *
 * Locale switching, language list, and current-locale state are provided
 * by the ManagesLocale trait.
 */
final class HeadlessLanguageSelector extends Component
{
    use ManagesLocale;

    /**
     * Initialise component state from the current request context.
     */
    public function mount(): void
    {
        $this->initLocaleState();
    }

    /**
     * Render the headless selector view.
     */
    public function render(): View
    {
        return view('lingua::selector.headless');
    }
}
