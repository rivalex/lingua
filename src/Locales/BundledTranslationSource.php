<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Locales;

use Rivalex\Lingua\Contracts\BaseTranslationSource;
use Rivalex\Lingua\Support\PathGuard;

/**
 * Default implementation of BaseTranslationSource.
 *
 * Phase 1: the bundled translations directory is empty (.gitkeep only),
 * so available() returns [] and translationsFor() returns [].
 *
 * Phase 2: populate resources/translations/{locale}/ with PHP group files.
 * Override lingua.base_translations_path in config to point to a custom
 * directory, e.g. for a rivalex/lingua-translations satellite package.
 *
 * Only PHP files inside per-locale subdirectories are loaded. Sibling
 * {locale}.json files are intentionally ignored — the dataset is PHP-only.
 */
final class BundledTranslationSource implements BaseTranslationSource
{
    public function __construct(
        private readonly string $basePath,
    ) {}

    /**
     * Return locale codes that have at least one translation file in the bundle.
     *
     * @return array<int, string>
     */
    public function available(): array
    {
        if (! is_dir($this->basePath)) {
            return [];
        }

        $codes = [];

        foreach (glob($this->basePath.'/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $codes[] = basename($dir);
        }

        return $codes;
    }

    /**
     * Return flat translation entries for the given locale from the bundle.
     *
     * @return array<int, array{locale: string, group: string, key: string, value: string, is_vendor: bool, vendor: string|null}>
     */
    public function translationsFor(string $locale): array
    {
        PathGuard::assertSafeSegment($locale, 'locale');

        $result = [];
        $localeDir = $this->basePath.'/'.$locale;

        if (! is_dir($localeDir)) {
            return [];
        }

        foreach (glob($localeDir.'/*.php') ?: [] as $file) {
            $group = basename($file, '.php');
            $translations = include $file;

            if (! is_array($translations)) {
                continue;
            }

            $this->flatten($translations, $result, $locale, $group, '');
        }

        return $result;
    }

    private function flatten(array $array, array &$result, string $locale, string $group, string $prefix): void
    {
        foreach ($array as $key => $value) {
            $fullKey = $prefix !== '' ? $prefix.'.'.$key : (string) $key;

            if (is_array($value)) {
                $this->flatten($value, $result, $locale, $group, $fullKey);
            } else {
                $result[] = [
                    'locale' => $locale,
                    'group' => $group,
                    'key' => $fullKey,
                    'value' => $value ?? '',
                    'is_vendor' => false,
                    'vendor' => null,
                ];
            }
        }
    }
}
