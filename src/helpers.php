<?php

if (! function_exists('defaultLocale')) {
    function defaultLocale(): string
    {
        return app()->getFallbackLocale();
    }
}

if (! function_exists('languageCode')) {
    function languageCode(?string $locale = null): string
    {
        return \Illuminate\Support\Str::of($locale ?? defaultLocale())->lower()->replace('_', '-');
    }
}
