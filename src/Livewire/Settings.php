<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Rivalex\Lingua\Enums\SelectorMode;
use Rivalex\Lingua\Models\LinguaSetting;

/**
 * Settings component.
 *
 * Full-page component for managing DB-persisted Lingua package settings.
 * Presents toggles and selects for selector behaviour, writing changes
 * via LinguaSetting::set() so they persist across requests.
 */
#[Title('Lingua Settings')]
final class Settings extends Component
{
    /**
     * Whether the language selector should display flag icons.
     */
    public bool $showFlags = true;

    /**
     * The rendering mode for the language selector (sidebar|modal|dropdown|headless).
     */
    public string $selectorMode = 'sidebar';

    /**
     * Load current values from DB, falling back to config() then a hardcoded default.
     */
    public function mount(): void
    {
        $this->showFlags = (bool) LinguaSetting::get(
            LinguaSetting::KEY_SHOW_FLAGS,
            config('lingua.selector.show_flags', true),
        );

        $this->selectorMode = (string) LinguaSetting::get(
            LinguaSetting::KEY_SELECTOR_MODE,
            config('lingua.selector.mode', SelectorMode::Sidebar->value),
        );
    }

    /**
     * All available selector modes, used to populate the select input.
     *
     * @return list<SelectorMode>
     */
    #[Computed(cache: true)]
    public function availableModes(): array
    {
        return SelectorMode::cases();
    }

    /**
     * Persist the current property values to the database.
     *
     * Dispatches a 'settings-saved' browser event on success so the UI
     * can display a confirmation without a full page reload.
     */
    public function save(): void
    {
        $validModes = array_column(SelectorMode::cases(), 'value');

        if (! in_array($this->selectorMode, $validModes, strict: true)) {
            $this->selectorMode = SelectorMode::Sidebar->value;
        }

        LinguaSetting::set(LinguaSetting::KEY_SHOW_FLAGS, $this->showFlags);
        LinguaSetting::set(LinguaSetting::KEY_SELECTOR_MODE, $this->selectorMode);

        $this->dispatch('settings-saved');
    }

    /**
     * Render the settings page view.
     */
    public function render(): View
    {
        return view('lingua::settings');
    }
}
