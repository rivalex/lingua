<?php

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
        } else {
            $locale = $defaultLocale;
        }
        app()->setFallbackLocale($defaultLocale);
        app()->setLocale($locale);
        Session::put($sessionLocale, $locale);

        return $next($request);
    }
}
