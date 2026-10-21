<?php

declare(strict_types=1);

use Illuminate\Support\Arr;

it('every bundled locale lingua.php has all keys present in en/lingua.php', function (): void {
    $langDir = __DIR__.'/../../../resources/lang';
    $enFile = $langDir.'/en/lingua.php';

    expect(file_exists($enFile))->toBeTrue('en/lingua.php must exist');

    $enKeys = array_keys(Arr::dot(require $enFile));

    $locales = array_filter(
        array_map('basename', glob($langDir.'/*', GLOB_ONLYDIR)),
        fn (string $loc) => $loc !== 'en',
    );

    expect($locales)->not->toBeEmpty('at least one non-en locale must exist');

    foreach ($locales as $locale) {
        $file = $langDir.'/'.$locale.'/lingua.php';

        if (! file_exists($file)) {
            continue; // locale without a lingua.php is not part of the bundled set — skip
        }

        $localeKeys = array_keys(Arr::dot(require $file));
        $missing = array_diff($enKeys, $localeKeys);

        expect($missing)
            ->toBeEmpty(
                sprintf(
                    "Locale '%s' is missing %d key(s) from en/lingua.php:\n  %s",
                    $locale,
                    count($missing),
                    implode("\n  ", array_slice($missing, 0, 10)).(count($missing) > 10 ? "\n  …and ".(count($missing) - 10).' more' : ''),
                )
            );
    }
});
