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

/**
 * Settings component.
 *
 * Full-page component for managing DB-persisted Lingua package settings.
 * Presents toggles and selects for selector, routing, and editor behaviour,
 * writing changes via LinguaSetting::set() so they persist across requests.
 */
#[Title('Lingua Settings')]
final class Settings extends Component
{
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

    /** Whether wire:navigate is used on internal Lingua redirects. */
    public bool $navigate = false;

    /** Blade layout component for full-page Lingua views (empty string = Livewire default). */
    #[Validate('nullable|string|max:200')]
    public string $layout = '';

    /** Whether translation links are active in language rows and statistics. */
    public bool $linksTranslationsEnabled = true;

    /** Route name used for all translation page links. */
    #[Validate('required|string|max:120|regex:/^[a-zA-Z0-9._:-]+$/')]
    public string $linksTranslationsRoute = 'lingua.translations';

    /** CSS top offset for the sticky filter bar (integer → rem, or CSS string). */
    #[Validate('required|string|max:100')]
    public string $uiStickyTop = '0';

    // -------------------------------------------------------------------------
    // Editor toolbar
    // -------------------------------------------------------------------------

    /** Editor toolbar feature toggles (keyed by feature name). */
    public array $editor = [];

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

        $this->navigate = (bool) LinguaSetting::get(
            LinguaSetting::KEY_NAVIGATE,
            config('lingua.navigate', false),
        );

        $this->layout = (string) LinguaSetting::get(
            LinguaSetting::KEY_LAYOUT,
            config('lingua.layout', ''),
        );

        $this->linksTranslationsEnabled = (bool) LinguaSetting::get(
            LinguaSetting::KEY_LINKS_TRANSLATIONS_ENABLED,
            config('lingua.links.translations.enabled', true),
        );

        $this->linksTranslationsRoute = (string) LinguaSetting::get(
            LinguaSetting::KEY_LINKS_TRANSLATIONS_ROUTE,
            config('lingua.links.translations.route', 'lingua.translations'),
        );

        $rawStickyTop = LinguaSetting::get(
            LinguaSetting::KEY_UI_STICKY_TOP,
            config('lingua.ui.sticky_top', 0),
        );
        $this->uiStickyTop = (string) $rawStickyTop;

        $this->editor = (array) LinguaSetting::get(
            LinguaSetting::KEY_EDITOR,
            config('lingua.editor', []),
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
        LinguaSetting::set(LinguaSetting::KEY_NAVIGATE, $this->navigate);
        LinguaSetting::set(LinguaSetting::KEY_LAYOUT, $this->layout);
        LinguaSetting::set(LinguaSetting::KEY_LINKS_TRANSLATIONS_ENABLED, $this->linksTranslationsEnabled);
        LinguaSetting::set(LinguaSetting::KEY_LINKS_TRANSLATIONS_ROUTE, $this->linksTranslationsRoute);

        $stickyValue = is_numeric($this->uiStickyTop)
            ? (int) $this->uiStickyTop
            : $this->uiStickyTop;
        LinguaSetting::set(LinguaSetting::KEY_UI_STICKY_TOP, $stickyValue);

        if (! empty($this->editor)) {
            LinguaSetting::set(LinguaSetting::KEY_EDITOR, $this->editor);
        }

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
