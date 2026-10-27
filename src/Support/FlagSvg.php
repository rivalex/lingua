<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Support;

/**
 * Inline SVG flag for a locale, from the flags shipped in resources/flags.
 *
 * Lookup: the full code (`de_AT` -> `de-at`), then the base language (`sr_Cyrl` -> `sr`),
 * then the neutral flag. File names are matched case-insensitively, so regional codes
 * resolve the same way on case-sensitive filesystems.
 */
final class FlagSvg
{
    private const DIR = __DIR__.'/../../resources/flags';

    /** @var array<string, array<string, string>> lowercase file name => file name, per set */
    private static array $index = [];

    /** @var array<string, string> file path => SVG markup */
    private static array $markup = [];

    public static function html(string $code, bool $circle = false, string $class = ''): string
    {
        $svg = self::markup(self::path($code, $circle));

        if ($class === '') {
            return $svg;
        }

        return (string) preg_replace('/<svg\b/', '<svg class="'.e($class).'"', $svg, 1);
    }

    /**
     * Absolute path of the flag file used for the given locale code.
     */
    public static function path(string $code, bool $circle = false): string
    {
        $set = $circle ? 'circle' : 'square';
        $prefix = $circle ? 'circle-language-' : 'language-';
        $index = self::index($set);

        $full = strtolower(str_replace('_', '-', trim($code)));
        $base = strtolower((string) preg_split('/[-_]/', trim($code))[0]);

        foreach (array_unique([$full, $base]) as $candidate) {
            $name = $prefix.$candidate.'.svg';

            if ($candidate !== '' && isset($index[$name])) {
                return self::DIR.'/'.$set.'/'.$index[$name];
            }
        }

        return self::DIR.'/'.$set.'/'.($circle ? 'circle-' : '').'country-xx.svg';
    }

    /**
     * @return array<string, string>
     */
    private static function index(string $set): array
    {
        if (! isset(self::$index[$set])) {
            self::$index[$set] = [];

            foreach (glob(self::DIR.'/'.$set.'/*.svg') ?: [] as $file) {
                self::$index[$set][strtolower(basename($file))] = basename($file);
            }
        }

        return self::$index[$set];
    }

    private static function markup(string $path): string
    {
        return self::$markup[$path] ??= trim((string) file_get_contents($path));
    }
}
