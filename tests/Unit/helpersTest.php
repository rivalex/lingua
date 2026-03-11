<?php

it('can access the app configuration', function () {
    expect(config('lingua'))->toBeArray()
                            ->and(config('lingua.routes_prefix'))->toBe('lingua');
});

test('The helper linguaDefaultLocale function exists', function () {
    expect(function_exists('linguaDefaultLocale'))->toBeTrue();
});

it('returns the app default locale', function () {
    expect(linguaDefaultLocale())->toBe('en');
});

test('The helper linguaLanguageCode function exists', function () {
    expect(function_exists('linguaLanguageCode'))->toBeTrue();
});

it('formats the locale as a lowercase language code with hyphen', function () {
    expect(linguaLanguageCode('EN_US'))->toBe('en-us')
                                       ->and(linguaLanguageCode('pt_BR'))->toBe('pt-br');
});

it('uses the actual locale code when none is provided', function () {
    app()->setLocale('es_MX');
    expect(linguaLanguageCode())->toBe('es-mx');
});
