<?php

declare(strict_types=1);

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

if (! function_exists('linguaStorageDriver')) {
    /**
     * Returns the configured storage driver ('database' or 'file').
     * Reads ONLY from config — never from LinguaSetting (avoids circular DB read in file-mode).
     */
    function linguaStorageDriver(): string
    {
        return config('lingua.storage.driver', 'database');
    }
}

if (! function_exists('linguaIsFileMode')) {
    /**
     * Returns true when the file storage driver is active.
     */
    function linguaIsFileMode(): bool
    {
        return linguaStorageDriver() === 'file';
    }
}

if (! function_exists('linguaAssetUrl')) {
    /**
     * Returns a cache-busted URL for a compiled package asset.
     *
     * Appends ?v=<filemtime> so browsers invalidate their cache whenever
     * the file changes, while the route still carries max-age=31536000.
     */
    function linguaAssetUrl(string $path): string
    {
        $file = dirname(__DIR__).'/src/dist/'.$path;
        $version = is_file($file) ? (string) filemtime($file) : (string) config('lingua.version', '1');

        return route('lingua.assets', $path).'?v='.$version;
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
        return Str::of($locale ?? app()->getLocale())->replace('_', '-')->toString();
    }
}
