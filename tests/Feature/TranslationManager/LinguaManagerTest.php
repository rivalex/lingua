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

it('short-circuits to file-only for a non-wildcard vendor namespace', function (): void {
    // namespace !== null && !== '*' → early return before DB loaders run
    $result = app('translation.loader')->load('en', 'validation', 'some-package');

    expect($result)->toBeArray();
});

it('merges file and DB sources for the wildcard namespace', function (): void {
    $result = app('translation.loader')->load('en', 'auth', '*');

    expect($result)->toBeArray();
});
