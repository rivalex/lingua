<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Support;

/**
 * Inline SVG flag for a locale, from the flags shipped in resources/flags.
 *
 * Lookup: the full code (`de_AT` -> `de-at`), then the base language (`sr_Cyrl` -> `sr`),
 * then the flag of the country where the language is mainly spoken (see COUNTRY), then the
 * neutral flag. File names are matched case-insensitively, so regional codes
 * resolve the same way on case-sensitive filesystems.
 */
final class FlagSvg
{
    private const DIR = __DIR__.'/../../resources/flags';

    /**
     * Country flag for registry languages that have no language flag in a set.
     *
     * Languages without a state of their own whose country flag would be contested
     * (ckb, tt, ug) are left out on purpose and keep the neutral flag, as does `eo`
     * in the square set.
     *
     * @var array<string, string> base language => country code
     */
    private const COUNTRY = [
        'bho' => 'in',
        'ceb' => 'ph',
        'doi' => 'in',
        'ee' => 'gh',
        'fil' => 'ph',
        'fy' => 'nl',
        'gd' => 'gb-sct',
        'haw' => 'us-hi',
        'mai' => 'in',
        'mni' => 'in',
        'oc' => 'fr',
        'om' => 'et',
        'or' => 'in',
        'sa' => 'in',
        'sc' => 'it',
    ];

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

        $country = ($circle ? 'circle-' : '').'country-'.(self::COUNTRY[$base] ?? 'xx').'.svg';

        return self::DIR.'/'.$set.'/'.($index[$country] ?? ($circle ? 'circle-' : '').'country-xx.svg');
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
