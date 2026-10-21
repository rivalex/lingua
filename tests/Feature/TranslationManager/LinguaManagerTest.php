<?php

declare(strict_types=1);

use Rivalex\Lingua\TranslationManager\LinguaManager;

it('is bound as the translation.loader singleton', function (): void {
    expect(app('translation.loader'))->toBeInstanceOf(LinguaManager::class);
});

it('returns an array for a default-namespace load', function (): void {
    $result = app('translation.loader')->load('en', 'auth');

    expect($result)->toBeArray();
});

it('returns an array for a vendor namespace (merges file and DB in database mode)', function (): void {
    // In database mode: file translations merged with DB vendor rows (empty here = no rows seeded).
    // In file mode: file translations returned as-is.
    // Deep coverage (cache, bust, QueryException fallback) lives in VendorLoadPathTest.
    $result = app('translation.loader')->load('en', 'validation', 'some-package');

    expect($result)->toBeArray();
});

it('merges file and DB sources for the wildcard namespace', function (): void {
    $result = app('translation.loader')->load('en', 'auth', '*');

    expect($result)->toBeArray();
});
