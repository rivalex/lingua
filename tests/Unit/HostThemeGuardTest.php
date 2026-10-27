<?php

declare(strict_types=1);

use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Livewire\Selector\Icon;

/*
 * Guard for the host theme rule: Lingua ships no CSS, so every Tailwind class lives as a literal
 * under resources/views and uses host tokens only.
 */

/**
 * @return array<string, string> relative path => contents, for every file under resources/views
 */
function hostThemeViews(): array
{
    $root = realpath(__DIR__.'/../../resources/views');
    $files = [];

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
            // Relative path with forward slashes on every OS, so the allowlists below match on Windows too.
            $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen($root) + 1));
            $files[$relative] = (string) file_get_contents($file->getPathname());
        }
    }

    return $files;
}

/**
 * Whether $token appears in $source as a whole class token (not as part of a longer class).
 */
function hostThemeHasToken(string $source, string $token): bool
{
    return preg_match('/(?<![\w:\[\]\/.-])'.preg_quote($token, '/').'(?![\w:\[\]\/-])/', $source) === 1;
}

/**
 * @return list<string>
 */
function hostThemeTokens(string ...$classLists): array
{
    $tokens = [];
    foreach ($classLists as $list) {
        foreach (preg_split('/\s+/', trim($list)) ?: [] as $token) {
            if ($token !== '') {
                $tokens[$token] = $token;
            }
        }
    }

    return array_values($tokens);
}

/**
 * Class lists the Selector\Icon component builds in PHP, for every size, with and without flags.
 *
 * @return list<string>
 */
function hostThemeIconClassLists(): array
{
    $lists = [];

    foreach (['sm', 'md', 'lg', 'xl'] as $size) {
        foreach ([true, false] as $showFlags) {
            $icon = new Icon;
            $icon->size = $size;
            $icon->showFlags = $showFlags;

            $build = new ReflectionMethod($icon, $showFlags ? 'buildFlag' : 'buildDefault');
            $build->invoke($icon);

            $lists[] = $icon->flagClass;
            foreach ($icon->textIconClasses as $classes) {
                $lists[] = $classes;
            }
        }
    }

    return $lists;
}

it('keeps the views free of stylesheet, font family and non-host colour tokens', function (string $pattern, string $label): void {
    $offenders = [];
    foreach (hostThemeViews() as $path => $contents) {
        if (preg_match($pattern, $contents) === 1) {
            $offenders[] = $path;
        }
    }

    expect($offenders)->toBe([], "{$label} found in views");
})->with([
    'dark-scope component' => ['/dark-scope/', 'dark-scope'],
    'style block' => ['/<style/', '<style'],
    'font family' => ['/font-(sans|serif)\b/', 'font-sans or font-serif'],
    'non-zinc neutral' => ['/-(gray|slate|neutral|stone)-\d/', 'gray, slate, neutral or stone shade'],
    'violet' => ['/violet-/', 'violet'],
    'violet badge' => ['/color="violet"/', 'color="violet"'],
    'stylesheet link' => ['/lingua\.min\.css/', 'lingua.min.css'],
]);

it('keeps the removed class names out of the views', function (string $class): void {
    $offenders = [];
    foreach (hostThemeViews() as $path => $contents) {
        if (preg_match('/(?<![\w-])'.preg_quote($class, '/').'(?![\w-])/', $contents) === 1) {
            $offenders[] = $path;
        }
    }

    expect($offenders)->toBe([], "removed class {$class} found in views");
})->with([
    'lingua-watermark', 'lingua-logo', 'lingua-modal', 'lingua-row', 'lingua-preview', 'lingua-editor',
    'editor-button', 'editor-headings', 'source-code-label', 'editor-source', 'language-sort-container',
    'translation-group-key', 'vendor_item', 'selector_icon', 'selector_item', 'header_h2',
]);

it('allows an inline style only in the three files that print a computed value', function (): void {
    $allowed = [
        'statistics/partials/_coverage.blade.php',
        'translations.blade.php',
        'language/row.blade.php',
    ];

    $offenders = [];
    foreach (hostThemeViews() as $path => $contents) {
        if (in_array($path, $allowed, true)) {
            continue;
        }
        if (preg_match('/\sstyle\s*=/', $contents) === 1) {
            $offenders[] = $path;
        }
    }

    expect($offenders)->toBe([]);
});

it('writes every class token built by LinguaType literally in the views', function (): void {
    $source = implode("\n", hostThemeViews());

    $lists = [];
    foreach (LinguaType::cases() as $type) {
        $lists[] = $type->color();

        preg_match('/^<div class="([^"]*)"/', $type->labelWithIcon(), $wrapper);
        $lists[] = $wrapper[1] ?? '';
    }

    $missing = array_values(array_filter(
        hostThemeTokens(...$lists),
        static fn (string $token): bool => ! hostThemeHasToken($source, $token),
    ));

    expect($missing)->toBe([]);
});

it('writes every class token built by Selector\\Icon literally in the views', function (): void {
    $source = implode("\n", hostThemeViews());

    $missing = array_values(array_filter(
        hostThemeTokens(...hostThemeIconClassLists()),
        static fn (string $token): bool => ! hostThemeHasToken($source, $token),
    ));

    expect($missing)->toBe([]);
});
