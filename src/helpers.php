<?php

use Illuminate\Support\Str;

if (! function_exists('linguaDefaultLocale')) {
    /**
     * Retrieves the default locale for the application.
     *
     * The method attempts to fetch the fallback locale from the application configuration.
     * If no fallback locale is set, it defaults to the value specified in the 'lingua.default_locale' configuration.
     *
     * @return string The default locale for the application.
     */
    function linguaDefaultLocale(): string
    {
        return app()->getFallbackLocale() ?? config('lingua.default_locale');
    }
}

if (! function_exists('linguaLanguageCode')) {
    /**
     * Converts the given locale string to a standardized format by converting it to lowercase
     * and replacing underscores with hyphens. If no locale is provided, the default locale
     * is used.
     *
     * @param  string|null  $locale  The locale to be formatted. If null, the default locale will be used.
     * @return string The formatted language code in lowercase with hyphens instead of underscores.
     */
    function linguaLanguageCode(?string $locale = null): string
    {
        return Str::of($locale ?? app()->getLocale())->lower()->replace('_', '-');
    }
}
