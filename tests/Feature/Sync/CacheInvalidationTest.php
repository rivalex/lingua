<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\TranslationManager\CacheKey;

test('syncToDatabase invalidates only lingua cache keys, leaves unrelated cache intact', function (): void {
    $store = Cache::store(config('lingua.cache.store'));

    // Pre-populate an unrelated cache entry — must survive the sync
    $store->forever('app.unrelated_key', 'preserved_value');

    // Create a translation so 'single' group for 'en' will be touched by syncToDatabase
    Translation::create([
        'group' => 'single',
        'key' => 'cache_test_'.uniqid(),
        'text' => ['en' => 'Hello cache test'],
        'is_vendor' => false,
        'vendor' => null,
    ]);

    // Pre-cache the lingua key that should be invalidated
    $linguaKey = CacheKey::forGroup('en', 'single');
    $store->forever($linguaKey, ['stale' => 'data']);

    // Run sync — should invalidate the touched lingua key but NOT the unrelated key
    Translation::syncToDatabase();

    expect($store->get('app.unrelated_key'))->toBe('preserved_value')
        ->and($store->has($linguaKey))->toBeFalse();
});
