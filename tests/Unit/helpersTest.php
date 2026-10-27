<?php

use Rivalex\Lingua\Facades\Lingua;
use Rivalex\Lingua\Models\Language;

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

it('formats the locale as a language code with hyphen', function () {
    expect(linguaLanguageCode('EN_US'))->toBe('EN-US')
        ->and(linguaLanguageCode('pt_BR'))->toBe('pt-BR');
});

it('uses the actual locale code when none is provided', function () {
    app()->setLocale('es_MX');
    expect(linguaLanguageCode())->toBe('es-MX');
});

it('can return the language direction for `rtl` languages', function () {
    Language::factory()->create(['code' => 'ar', 'is_default' => false, 'direction' => 'rtl']);
    app()->setLocale('ar');
    expect(app()->getLocale())->toBe('ar')
        ->and(Lingua::getDirection())->toBe('rtl');
    Language::where('code', 'ar')->delete();
});

it('can return the language direction for `ltr` languages', function () {
    app()->setLocale('en');
    expect(Lingua::getDirection())->toBe('ltr');
});
