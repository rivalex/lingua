<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\TranslationManager\CacheKey;

it('caches translations per locale and group after first load', function (): void {
    Translation::create([
        'group' => 'test_cache',
        'key' => 'hello',
        'text' => ['en' => 'Hello.'],
        'type' => 'text',
        'is_vendor' => false,
    ]);

    $key = CacheKey::forGroup('en', 'test_cache');

    expect(Cache::has($key))->toBeFalse();

    Translation::getTranslationsForGroup('en', 'test_cache');

    expect(Cache::has($key))->toBeTrue();
});

it('forgets only the affected locale cache key on model save', function (): void {
    Translation::create([
        'group' => 'test_cache',
        'key' => 'hello',
        'text' => ['en' => 'Hello.', 'it' => 'Ciao.'],
        'type' => 'text',
        'is_vendor' => false,
    ]);

    Translation::create([
        'group' => 'test_other',
        'key' => 'bye',
        'text' => ['en' => 'Bye.', 'it' => 'Ciao.'],
        'type' => 'text',
        'is_vendor' => false,
    ]);

    // Warm all four cache entries
    Translation::getTranslationsForGroup('en', 'test_cache');
    Translation::getTranslationsForGroup('it', 'test_cache');
    Translation::getTranslationsForGroup('en', 'test_other');
    Translation::getTranslationsForGroup('it', 'test_other');

    // Save a translation in the test_cache group only
    $translation = Translation::where('group', 'test_cache')->where('key', 'hello')->first();
    $translation->text = array_merge($translation->text, ['en' => 'Hi.', 'it' => 'Salve.']);
    $translation->save();

    // test_cache cache cleared for both locales
    expect(Cache::has(CacheKey::forGroup('en', 'test_cache')))->toBeFalse();
    expect(Cache::has(CacheKey::forGroup('it', 'test_cache')))->toBeFalse();

    // test_other cache untouched
    expect(Cache::has(CacheKey::forGroup('en', 'test_other')))->toBeTrue();
    expect(Cache::has(CacheKey::forGroup('it', 'test_other')))->toBeTrue();
});

it('forgets cache for all locales in text on model delete', function (): void {
    $translation = Translation::create([
        'group' => 'test_cache',
        'key' => 'throttle',
        'text' => ['en' => 'Too many attempts.', 'fr' => 'Trop de tentatives.'],
        'type' => 'text',
        'is_vendor' => false,
    ]);

    Translation::getTranslationsForGroup('en', 'test_cache');
    Translation::getTranslationsForGroup('fr', 'test_cache');

    $translation->delete();

    expect(Cache::has(CacheKey::forGroup('en', 'test_cache')))->toBeFalse();
    expect(Cache::has(CacheKey::forGroup('fr', 'test_cache')))->toBeFalse();
});
