<?php

declare(strict_types=1);

use Rivalex\Lingua\Locales\BundledTranslationSource;

test('BundledTranslationSource available returns generated locales', function (): void {
    $source = new BundledTranslationSource(__DIR__.'/../../resources/translations');

    $available = $source->available();

    expect($available)
        ->toContain('ar', 'de', 'es', 'fr', 'it', 'pt_BR', 'zh_CN');
});

test('BundledTranslationSource translationsFor returns flat entries with correct keys', function (): void {
    $source = new BundledTranslationSource(__DIR__.'/../../resources/translations');

    $translations = $source->translationsFor('it');

    expect($translations)->not->toBeEmpty();

    $first = $translations[0];

    expect($first)->toHaveKeys(['locale', 'group', 'key', 'value', 'is_vendor', 'vendor']);
    expect($first['locale'])->toBe('it');
    expect($first['value'])->toBeString()->not->toBeEmpty();
});
