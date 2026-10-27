<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Support;

/**
 * Reads translation files from the filesystem into a flat array of entries.
 *
 * This class is NOT final so it can be extended or replaced in tests.
 */
class TranslationFileReader
{
    /**
     * Collect all translation entries for a single locale from the filesystem.
     *
     * Reads core JSON, core PHP, vendor JSON, and vendor PHP files.
     * Returns a flat array; each item contains: locale, group, key, value, is_vendor, vendor.
     *
     * @param  string  $langPath  Absolute path to the lang directory
     * @param  string  $locale  ISO locale code to collect files for
     * @return array<int, array{locale: string, group: string, key: string, value: string, is_vendor: bool, vendor: string|null}>
     */
    public function collect(string $langPath, string $locale): array
    {
        $result = [];

        // 1) Core JSON
        $jsonFile = $langPath.'/'.$locale.'.json';
        if (file_exists($jsonFile)) {
            $decoded = json_decode(file_get_contents($jsonFile), true);
            if (is_array($decoded)) {
                foreach ($decoded as $key => $value) {
                    $result[] = [
                        'locale' => $locale,
                        'group' => 'single',
                        'key' => $key,
                        'value' => $value ?? '',
                        'is_vendor' => false,
                        'vendor' => null,
                    ];
                }
            }
        }

        // 2) Core PHP
        $langFolder = $langPath.'/'.$locale;
        if (is_dir($langFolder)) {
            foreach (glob($langFolder.'/*.php') ?: [] as $file) {
                $group = basename($file, '.php');
                $groupTranslations = include $file;
                if (is_array($groupTranslations)) {
                    $this->flatten($groupTranslations, $result, $locale, $group, false, null);
                }
            }
        }

        // 3) Vendor JSON + PHP
        foreach ($this->discoverVendorPackages($langPath) as $vendor) {
            $vendorRoot = $langPath.'/vendor/'.$vendor;

            $vendorJson = $vendorRoot.'/'.$locale.'.json';
            if (file_exists($vendorJson)) {
                $decoded = json_decode(file_get_contents($vendorJson), true);
                if (is_array($decoded)) {
                    foreach ($decoded as $key => $value) {
                        $result[] = [
                            'locale' => $locale,
                            'group' => 'single',
                            'key' => $key,
                            'value' => $value ?? '',
                            'is_vendor' => true,
                            'vendor' => $vendor,
                        ];
                    }
                }
            }

            $vendorLangFolder = $vendorRoot.'/'.$locale;
            if (is_dir($vendorLangFolder)) {
                foreach (glob($vendorLangFolder.'/*.php') ?: [] as $file) {
                    $group = basename($file, '.php');
                    $groupTranslations = include $file;
                    if (is_array($groupTranslations)) {
                        $this->flatten($groupTranslations, $result, $locale, $group, true, $vendor);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Discover all locale codes present in the lang directory.
     *
     * @param  string  $langPath  Absolute path to the lang directory
     * @return array<int, string>
     */
    public function discoverLocales(string $langPath): array
    {
        $locales = [];

        foreach (glob($langPath.'/*.json') ?: [] as $file) {
            $locales[] = basename($file, '.json');
        }

        foreach (glob($langPath.'/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $name = basename($dir);
            if ($name !== 'vendor') {
                $locales[] = $name;
            }
        }

        foreach ($this->discoverVendorPackages($langPath) as $vendor) {
            $vendorRoot = $langPath.'/vendor/'.$vendor;
            foreach (glob($vendorRoot.'/*.json') ?: [] as $file) {
                $locales[] = basename($file, '.json');
            }
            foreach (glob($vendorRoot.'/*', GLOB_ONLYDIR) ?: [] as $dir) {
                $locales[] = basename($dir);
            }
        }

        return array_values(array_unique($locales));
    }

    /**
     * Discover vendor package names from the vendor subdirectory.
     *
     * @param  string  $langPath  Absolute path to the lang directory
     * @return array<int, string>
     */
    public function discoverVendorPackages(string $langPath): array
    {
        $vendorDir = $langPath.'/vendor';
        if (! is_dir($vendorDir)) {
            return [];
        }

        return array_map('basename', glob($vendorDir.'/*', GLOB_ONLYDIR) ?: []);
    }

    /**
     * Recursively flatten a nested PHP translation array into flat key-value entries.
     *
     * @param  array<string, mixed>  $array
     * @param  array<int, array>  &$result
     * @param  string  $prefix  Current dot-separated key prefix
     */
    public function flatten(
        array $array,
        array &$result,
        string $locale,
        string $group,
        bool $isVendor,
        ?string $vendor,
        string $prefix = ''
    ): void {
        foreach ($array as $key => $value) {
            $fullKey = $prefix ? $prefix.'.'.$key : $key;

            if (is_array($value)) {
                $this->flatten($value, $result, $locale, $group, $isVendor, $vendor, $fullKey);
            } else {
                $result[] = [
                    'locale' => $locale,
                    'group' => $group,
                    'key' => $fullKey,
                    'value' => $value ?? '',
                    'is_vendor' => $isVendor,
                    'vendor' => $vendor,
                ];
            }
        }
    }
}
