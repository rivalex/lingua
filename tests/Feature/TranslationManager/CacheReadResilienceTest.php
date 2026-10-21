<?php

declare(strict_types=1);

use Illuminate\Contracts\Cache\Store;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Schema;
use Rivalex\Lingua\Models\Translation;

/**
 * Cache store double: counts calls and fails on demand.
 */
final class FailingLinguaStore implements Store
{
    public static bool $getThrows = true;

    public static bool $foreverThrows = false;

    public static int $getCalls = 0;

    public static int $foreverCalls = 0;

    public static function reset(): void
    {
        self::$getThrows = true;
        self::$foreverThrows = false;
        self::$getCalls = 0;
        self::$foreverCalls = 0;
    }

    public function get($key)
    {
        self::$getCalls++;

        if (self::$getThrows) {
            throw new RuntimeException('cache down');
        }

        return null;
    }

    public function many(array $keys)
    {
        return array_fill_keys($keys, null);
    }

    public function put($key, $value, $seconds)
    {
        return true;
    }

    public function putMany(array $values, $seconds)
    {
        return true;
    }

    public function increment($key, $value = 1)
    {
        return false;
    }

    public function decrement($key, $value = 1)
    {
        return false;
    }

    public function forever($key, $value)
    {
        self::$foreverCalls++;

        if (self::$foreverThrows) {
            throw new RuntimeException('cache down');
        }

        return true;
    }

    public function touch($key, $seconds)
    {
        return true;
    }

    public function forget($key)
    {
        return true;
    }

    public function flush()
    {
        return true;
    }

    public function getPrefix()
    {
        return '';
    }
}

beforeEach(function (): void {
    FailingLinguaStore::reset();

    Cache::extend('lingua-failing', fn () => Cache::repository(new FailingLinguaStore));

    config([
        'cache.stores.lingua-failing' => ['driver' => 'lingua-failing'],
        'lingua.cache.store' => 'lingua-failing',
    ]);

    Translation::create([
        'group' => 'resilience',
        'key' => 'hello',
        'text' => ['en' => 'Hello.'],
        'type' => 'text',
        'is_vendor' => false,
    ]);

    Translation::create([
        'group' => 'resilience_other',
        'key' => 'bye',
        'text' => ['en' => 'Bye.'],
        'type' => 'text',
        'is_vendor' => false,
    ]);

    Translation::create([
        'group' => 'resilience_vendor',
        'key' => 'vendor_hello',
        'text' => ['en' => 'Vendor hello.'],
        'type' => 'text',
        'is_vendor' => true,
        'vendor' => 'acme',
    ]);
});

it('returns database values when the cache read throws', function (): void {
    expect(Translation::getTranslationsForGroup('en', 'resilience'))->toBe(['hello' => 'Hello.']);
});

it('returns database vendor values when the cache read throws', function (): void {
    expect(Translation::getVendorTranslationsForGroup('en', 'acme', 'resilience_vendor'))
        ->toBe(['vendor_hello' => 'Vendor hello.']);
});

it('reports the cache exception', function (): void {
    Exceptions::fake();

    Translation::getTranslationsForGroup('en', 'resilience');

    Exceptions::assertReported(RuntimeException::class);
});

it('touches the failing store once per request', function (): void {
    Translation::getTranslationsForGroup('en', 'resilience');
    Translation::getTranslationsForGroup('en', 'resilience_other');

    expect(FailingLinguaStore::$getCalls)->toBe(1)
        ->and(FailingLinguaStore::$foreverCalls)->toBe(0);

    app()->forgetScopedInstances();

    Translation::getTranslationsForGroup('en', 'resilience');

    expect(FailingLinguaStore::$getCalls)->toBe(2);
});

it('returns database values and reports when the cache write-back throws', function (): void {
    FailingLinguaStore::$getThrows = false;
    FailingLinguaStore::$foreverThrows = true;

    Exceptions::fake();

    expect(Translation::getTranslationsForGroup('en', 'resilience'))->toBe(['hello' => 'Hello.']);

    Exceptions::assertReported(RuntimeException::class);
    expect(FailingLinguaStore::$foreverCalls)->toBe(1);
});

it('does not swallow database errors', function (): void {
    Schema::drop('language_lines');

    expect(fn () => Translation::getTranslationsForGroup('en', 'resilience'))
        ->toThrow(QueryException::class);
});
