<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Traits;

use Illuminate\Database\QueryException;
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
     * Captures a relative request URI (path + query string, no host) so the
     * open-redirect guard in changeLocale() never trips on a host mismatch
     * between config('app.url') and the real browsing host (local dev, staging,
     * custom domains).  Relative URIs are same-origin by construction.
     *
     * Call this inside the host component's mount() method.
     */
    protected function initLocaleState(): void
    {
        $this->currentLocale = app()->currentLocale();
        $this->currentUrl = request()->getRequestUri();
    }

    /**
     * Return all active languages ordered by sort position.
     *
     * @return Collection<int, Language>
     */
    #[Computed]
    public function languages(): Collection
    {
        try {
            return Language::query()->active()->get();
        } catch (QueryException) {
            return collect();
        }
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

        // Guard against open redirect.
        // currentUrl is a relative request URI captured at mount time; it always
        // starts with '/' and carries no host.  A component could theoretically
        // have $currentUrl mutated externally, so we enforce the invariant here:
        //   - Must start with exactly one '/' (not '//' = protocol-relative,
        //     not '/\' = backslash-relative, both used for open-redirect tricks).
        //   - Must not contain a scheme (any 'word:' prefix signals an absolute URL).
        $url = $this->currentUrl;
        $isSafePath = preg_match('#^/(?![/\\\])#', $url) === 1
            && ! preg_match('#^\w+:#', $url);
        if (! $isSafePath) {
            $url = '/';
        }

        Session::put(config('lingua.session_variable'), $locale);
        app()->setLocale($locale);
        $this->redirect(url: $url, navigate: (bool) config('lingua.navigate', false));
    }
}
