<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Rivalex\Lingua\Facades\Lingua;
use Rivalex\Lingua\Models\Language;

/**
 * Provides shared locale state and switching logic for Livewire selector components.
 *
 * Components using this trait must call initLocaleState() inside their mount() method.
 *
 * Provides:
 * - $currentLocale  — ISO code of the active locale at mount time
 * - $currentUrl     — URL to redirect back to after a locale switch
 * - languages()     — Computed collection of all active Language records
 * - initLocaleState() — Initialises $currentLocale and $currentUrl
 * - changeLocale()  — Validates and applies a locale switch, then redirects
 */
trait ManagesLocale
{
    /** The ISO 639-1 code of the locale active when the component was mounted. */
    public string $currentLocale = '';

    /** The URL to redirect to after a successful locale change. */
    public string $currentUrl = '';

    /**
     * Initialise locale state from the current request context.
     *
     * Call this inside the host component's mount() method.
     */
    protected function initLocaleState(): void
    {
        $this->currentLocale = app()->currentLocale();
        $this->currentUrl = url()->current();
    }

    /**
     * Return all active languages ordered by sort position.
     *
     * @return Collection<int, Language>
     */
    #[Computed]
    public function languages(): Collection
    {
        return Language::query()->active()->get();
    }

    /**
     * Switch the application locale to the given code.
     *
     * Silently ignores unknown locale codes to prevent unintended behaviour
     * from malformed or spoofed requests.
     *
     * @param  string  $locale  ISO 639-1 locale code to activate.
     */
    public function changeLocale(string $locale): void
    {
        if (! Lingua::hasLocale($locale)) {
            return;
        }

        Session::put(config('lingua.session_variable'), $locale);
        app()->setLocale($locale);
        $this->redirect(url: $this->currentUrl, navigate: true);
    }
}
