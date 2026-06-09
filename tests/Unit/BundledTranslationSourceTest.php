<?php

declare(strict_types=1);

use Rivalex\Lingua\Locales\BundledTranslationSource;

test('BundledTranslationSource available returns generated locales', function (): void {
    $source = new BundledTranslationSource(__DIR__.'/../../resources/translations');

    $available = $source->available();

    expect($available)
        ->toContain('ar', 'de', 'es', 'fr', 'it', 'pt_BR', 'zh_CN');
});

test('BundledTranslationSource does NOT import a sibling {locale}.json file', function (): void {
    $base = sys_get_temp_dir().'/bts_test_'.uniqid();
    $localeDir = $base.'/xx';
    mkdir($localeDir, 0755, true);

    // PHP group file — should be imported
    file_put_contents($localeDir.'/messages.php', "<?php\nreturn ['hello' => 'world'];\n");

    // Sibling JSON file — must NOT be imported (dead code removed in #3)
    file_put_contents($base.'/xx.json', json_encode(['json_key' => 'json_val']));

    $source = new BundledTranslationSource($base);
    $entries = $source->translationsFor('xx');

    $keys = array_column($entries, 'key');
    expect($keys)->toContain('hello')
        ->and($keys)->not->toContain('json_key');

    // Cleanup
    unlink($localeDir.'/messages.php');
    unlink($base.'/xx.json');
    rmdir($localeDir);
    rmdir($base);
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
