<?php

if (! function_exists('defaultLocale')) {
    function defaultLocale(): string
    {
        return app()->getFallbackLocale();
    }
}

if (! function_exists('lt_asset')) {
    function lt_asset(string $path): string
    {
        $public = public_path("vendor/lingua/{$path}");
        if (file_exists($public)) {
            return asset("vendor/lingua/{$path}");
        }

        // fallback route that serves from the package's src/dist
        return route('lingua.assets', ['path' => $path]);
    }
}

if (! function_exists('parseTranslation')) {
    function parseTranslation(string $translation, string $type): string
    {
        $converter = new \League\HTMLToMarkdown\HtmlConverter;

        return match ($type) {
            'html', 'text' => \Illuminate\Support\Str::trim($translation) ?? '',
            'markdown' => \Illuminate\Support\Str::trim($converter->convert($translation)) ?? '',
            default => ''
        };
    }
}

if (! function_exists('getParsedTranslation')) {
    function getParsedTranslation(string $translation, string $type): string
    {
        $string = \Illuminate\Support\Str::of($translation)->squish()->trim();

        return match ($type) {
            'text' => $string->toString(),
            'html' => $string->toString(),
            'markdown' => $string->toString(),
            default => $string->toString()
        };
    }
}
