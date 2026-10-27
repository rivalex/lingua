<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Rivalex\Lingua\Enums\SelectorMode;
use Rivalex\Lingua\Models\LinguaSetting;
use Rivalex\Lingua\Traits\AuthorizesLingua;

/**
 * Settings component.
 *
 * Full-page component for managing DB-persisted Lingua package settings.
 * Presents toggles and selects for selector and routing,
 * writing changes via LinguaSetting::set() so they persist across requests.
 */
#[Title('Lingua Settings')]
final class Settings extends Component
{
    use AuthorizesLingua;

    // -------------------------------------------------------------------------
    // Selector
    // -------------------------------------------------------------------------

    /** Whether the language selector should display flag icons. */
    public bool $showFlags = true;

    /** The rendering mode for the language selector (sidebar|modal|dropdown|headless). */
    public string $selectorMode = 'sidebar';

    // -------------------------------------------------------------------------
    // Routing & navigation
    // -------------------------------------------------------------------------

    /** CSS top offset for the sticky filter bar (integer → rem, or CSS string). */
    #[Validate(['required', 'string', 'regex:/^\d{1,4}(\.\d{1,2})?(px|rem|em|vh|%)?$/'])]
    public string $uiStickyTop = '0';

    /** Whether the shared navigation menu is shown on all Lingua admin pages. */
    public bool $navEnabled = true;

    // -------------------------------------------------------------------------
    // Lifecycle
    // -------------------------------------------------------------------------

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

        $rawStickyTop = LinguaSetting::get(
            LinguaSetting::KEY_UI_STICKY_TOP,
            config('lingua.ui.sticky_top', 0),
        );
        $this->uiStickyTop = (string) $rawStickyTop;

        $this->navEnabled = (bool) LinguaSetting::get(
            LinguaSetting::KEY_NAV_ENABLED,
            config('lingua.nav.enabled', true),
        );
    }

    // -------------------------------------------------------------------------
    // Computed
    // -------------------------------------------------------------------------

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

    // -------------------------------------------------------------------------
    // Actions
    // -------------------------------------------------------------------------

    /**
     * Persist all settings to the database.
     *
     * Dispatches a 'settings-saved' browser event on success so the UI
     * can display a confirmation without a full page reload.
     */
    public function save(): void
    {
        $this->validate();

        $validModes = array_column(SelectorMode::cases(), 'value');
        if (! in_array($this->selectorMode, $validModes, strict: true)) {
            $this->selectorMode = SelectorMode::Sidebar->value;
        }

        LinguaSetting::set(LinguaSetting::KEY_SHOW_FLAGS, $this->showFlags);
        LinguaSetting::set(LinguaSetting::KEY_SELECTOR_MODE, $this->selectorMode);
        LinguaSetting::set(LinguaSetting::KEY_UI_STICKY_TOP, $this->uiStickyTop);
        LinguaSetting::set(LinguaSetting::KEY_NAV_ENABLED, $this->navEnabled);

        $this->dispatch('settings-saved');
    }

    // -------------------------------------------------------------------------
    // Rendering
    // -------------------------------------------------------------------------

    /**
     * Render the settings page view.
     */
    public function render(): View
    {
        $view = view('lingua::settings');
        $layout = config('lingua.layout');

        return $layout ? $view->layout($layout) : $view;
    }
}
