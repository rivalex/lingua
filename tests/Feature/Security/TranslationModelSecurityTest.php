<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Rivalex\Lingua\Exceptions\VendorTranslationProtectedException;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\TranslationManager\CacheKey;

/*
 * F1 — rememberForever must be bounded to well-formed locale codes so a
 * request-driven locale cannot create unbounded forever-cached entries.
 */
it('does not cache translations for a malformed locale (F1)', function (): void {
    Translation::create([
        'group' => 'sec_group',
        'key' => 'hello',
        'text' => ['en' => 'Hello.'],
        'type' => 'text',
        'is_vendor' => false,
    ]);

    $evilLocale = 'x"evil';
    $key = CacheKey::forGroup($evilLocale, 'sec_group');

    expect(Cache::has($key))->toBeFalse();

    $result = Translation::getTranslationsForGroup($evilLocale, 'sec_group');

    // Closure still runs and returns a correct (empty) result …
    expect($result)->toBe([]);
    // … but no cache entry is created for the garbage locale.
    expect(Cache::has($key))->toBeFalse();
});

it('still caches translations for a well-formed locale (F1)', function (): void {
    Translation::create([
        'group' => 'sec_group',
        'key' => 'hello',
        'text' => ['it' => 'Ciao.'],
        'type' => 'text',
        'is_vendor' => false,
    ]);

    $key = CacheKey::forGroup('it', 'sec_group');

    expect(Cache::has($key))->toBeFalse();

    Translation::getTranslationsForGroup('it', 'sec_group');

    expect(Cache::has($key))->toBeTrue();
});

it('does not cache vendor translations for a malformed locale (F1)', function (): void {
    Translation::create([
        'group' => 'sec_group',
        'key' => 'hello',
        'text' => ['en' => 'Hello.'],
        'type' => 'text',
        'is_vendor' => true,
        'vendor' => 'acme',
    ]);

    $evilLocale = 'x"evil';
    $key = CacheKey::forVendorGroup($evilLocale, 'acme', 'sec_group');

    Translation::getVendorTranslationsForGroup($evilLocale, 'acme', 'sec_group');

    expect(Cache::has($key))->toBeFalse();
});

/*
 * F5 — the model-level guard must block locale removal on vendor rows so the
 * repository guard cannot be bypassed by calling forgetTranslation() directly.
 */
it('refuses to remove a locale from a vendor translation (F5)', function (): void {
    $translation = Translation::create([
        'group' => 'sec_group',
        'key' => 'hello',
        'text' => ['en' => 'Hello.', 'it' => 'Ciao.'],
        'type' => 'text',
        'is_vendor' => true,
        'vendor' => 'acme',
    ]);

    expect(fn () => $translation->forgetTranslation('en'))
        ->toThrow(VendorTranslationProtectedException::class);

    // Value must remain untouched.
    expect($translation->fresh()->text)->toHaveKey('en');
});

it('still removes a locale from a non-vendor translation (F5)', function (): void {
    $translation = Translation::create([
        'group' => 'sec_group',
        'key' => 'hello',
        'text' => ['en' => 'Hello.', 'it' => 'Ciao.'],
        'type' => 'text',
        'is_vendor' => false,
    ]);

    $translation->forgetTranslation('en');

    expect($translation->fresh()->text)->not->toHaveKey('en')
        ->and($translation->fresh()->text)->toHaveKey('it');
});

/*
 * F7 — the markdown-detection regex must not exhibit catastrophic backtracking
 * on crafted input, and oversized values must skip detection entirely.
 */
it('detects markdown links without catastrophic backtracking (F7)', function (): void {
    $crafted = '['.str_repeat('x', 5000).'](not-a-url';

    $pattern = '/^#{1,6}\s|\n#{1,6}\s|^\s*[-*+]\s|\[[^\]]+\]\(https?:/im';

    $start = microtime(true);
    preg_match($pattern, $crafted);
    $elapsed = microtime(true) - $start;

    expect($elapsed)->toBeLessThan(1.0);
});

it('skips type detection for oversized values via writeTranslation (F7)', function (): void {
    $method = new ReflectionMethod(Translation::class, 'writeTranslation');
    $method->setAccessible(true);

    $huge = '# '.str_repeat('a', 11000); // markdown marker + >10k chars

    $start = microtime(true);
    $method->invoke(null, [
        'locale' => 'en',
        'group' => 'sec_group',
        'key' => 'huge',
        'value' => $huge,
        'is_vendor' => false,
        'vendor' => null,
    ], 'en');
    $elapsed = microtime(true) - $start;

    expect($elapsed)->toBeLessThan(1.0);

    $row = Translation::where('group', 'sec_group')->where('key', 'huge')->first();
    expect($row->type->value)->toBe('text');
});
