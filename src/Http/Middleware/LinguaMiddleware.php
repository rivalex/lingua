<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Http\Middleware;

use Illuminate\Support\Facades\Session;
use Rivalex\Lingua\Models\Language;

class LinguaMiddleware
{
    public function handle($request, \Closure $next)
    {
        $sessionLocale = config('lingua.session_variable');

        try {
            $defaultLocale = Language::default()?->code ?? config('app.locale');
        } catch (\Throwable) {
            // Table missing (pre-migration) or DB unavailable — never take the
            // whole request down for a locale lookup.
            $defaultLocale = config('app.locale');
        }

        if (Session::has($sessionLocale)) {
            $locale = Session::get($sessionLocale, $defaultLocale);
            // Reject tampered or malformed session values — locale must be a valid ISO format.
            if (! is_string($locale) || ! preg_match('/^[a-zA-Z]{2,8}([_-][a-zA-Z0-9]{1,8})*$/', $locale)) {
                $locale = $defaultLocale;
            }
        } else {
            $locale = $defaultLocale;
        }

        app()->setFallbackLocale($defaultLocale);
        app()->setLocale($locale);

        // Write the session only on change: an unconditional put() marks the
        // session dirty and forces a save on every single request.
        if (Session::get($sessionLocale) !== $locale) {
            Session::put($sessionLocale, $locale);
        }

        return $next($request);
    }
}
