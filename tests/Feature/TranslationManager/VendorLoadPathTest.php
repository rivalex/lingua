<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\TranslationManager\CacheKey;

// ---------------------------------------------------------------------------
// Vendor load-path regression tests (Phase 6a)
// ---------------------------------------------------------------------------
// These tests verify that in database mode vendor namespaces are served from
// the DB (cached, merged over files) and that the cache/bust semantics match
// the non-namespaced behaviour.

it('database mode returns vendor row via load(locale, group, namespace)', function (): void {
    Translation::create([
        'group' => 'buttons',
        'key' => 'save',
        'type' => 'text',
        'text' => ['en' => 'Save from DB'],
        'is_vendor' => true,
        'vendor' => 'flux',
    ]);

    $result = app('translation.loader')->load('en', 'buttons', 'flux');

    expect($result)
        ->toBeArray()
        ->toHaveKey('save')
        ->and($result['save'])->toBe('Save from DB');
});

it('second vendor namespace load is served from cache with no extra DB queries', function (): void {
    Translation::create([
        'group' => 'buttons',
        'key' => 'cancel',
        'type' => 'text',
        'text' => ['en' => 'Cancel'],
        'is_vendor' => true,
        'vendor' => 'flux',
    ]);

    // Warm the cache
    app('translation.loader')->load('en', 'buttons', 'flux');

    $cacheKey = CacheKey::forVendorGroup('en', 'flux', 'buttons');
    expect(Cache::has($cacheKey))->toBeTrue();

    DB::enableQueryLog();
    app('translation.loader')->load('en', 'buttons', 'flux');
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect($queries)->toBeEmpty();
});

it('editing a vendor row busts the vendor cache key and next load returns updated value', function (): void {
    $row = Translation::create([
        'group' => 'messages',
        'key' => 'greeting',
        'type' => 'text',
        'text' => ['en' => 'Hello'],
        'is_vendor' => true,
        'vendor' => 'acme',
    ]);

    // Warm cache
    app('translation.loader')->load('en', 'messages', 'acme');

    $cacheKey = CacheKey::forVendorGroup('en', 'acme', 'messages');
    expect(Cache::has($cacheKey))->toBeTrue();

    // Update via model — triggers saved observer → busts vendor cache key
    $row->text = ['en' => 'Hi there'];
    $row->save();

    expect(Cache::has($cacheKey))->toBeFalse();

    $result = app('translation.loader')->load('en', 'messages', 'acme');

    expect($result['greeting'])->toBe('Hi there');
});

it('QueryException on vendor namespace load degrades gracefully to file result', function (): void {
    Schema::dropIfExists('language_lines');

    // Must not throw — LinguaManager::load() QueryException handler falls back to parent::load()
    $result = app('translation.loader')->load('en', 'validation', 'some-vendor');

    expect($result)->toBeArray();
});

it('non-namespaced load is byte-identical before and after vendor rows are added', function (): void {
    $before = app('translation.loader')->load('en', 'auth');

    // Vendor rows for an unrelated namespace must not pollute the 'auth' app-string load
    Translation::create([
        'group' => 'prompts',
        'key' => 'confirm',
        'type' => 'text',
        'text' => ['en' => 'Confirm'],
        'is_vendor' => true,
        'vendor' => 'ui-kit',
    ]);

    $after = app('translation.loader')->load('en', 'auth');

    expect($after)->toBe($before);
});
