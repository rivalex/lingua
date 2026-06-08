<?php

declare(strict_types=1);

use Rivalex\Lingua\Contracts\TranslationLoader;
use Rivalex\Lingua\Database\Db;
use Rivalex\Lingua\Exceptions\InvalidConfiguration;

it('Db loader implements the Rivalex TranslationLoader contract', function (): void {
    expect(new Db)->toBeInstanceOf(TranslationLoader::class);
});

it('returns an empty array for namespaced groups', function (): void {
    $loader = new Db;

    expect($loader->loadTranslations('en', 'auth', 'some-package'))->toBe([]);
    expect($loader->loadTranslations('en', 'auth', 'vendor'))->toBe([]);
});

it('passes through for wildcard namespace', function (): void {
    $loader = new Db;

    expect($loader->loadTranslations('en', 'auth', '*'))->toBeArray();
});

it('throws InvalidConfiguration when model class is not a valid Eloquent model', function (): void {
    config(['lingua.model' => stdClass::class]);

    $loader = new Db;

    expect(fn () => $loader->loadTranslations('en', 'auth'))
        ->toThrow(InvalidConfiguration::class);
});
