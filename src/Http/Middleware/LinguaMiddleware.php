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
        $defaultLocale = Language::default()?->code ?? config('app.locale');

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
        Session::put($sessionLocale, $locale);

        return $next($request);
    }
}
