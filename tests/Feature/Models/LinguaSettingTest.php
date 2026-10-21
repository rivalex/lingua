<?php

declare(strict_types=1);

use Rivalex\Lingua\Enums\SelectorMode;
use Rivalex\Lingua\Models\LinguaSetting;

// ---------------------------------------------------------------------------
// get() — fallback behaviour
// ---------------------------------------------------------------------------

it('returns the default when no row exists', function (): void {
    expect(LinguaSetting::get(LinguaSetting::KEY_SHOW_FLAGS, 'fallback'))->toBe('fallback');
});

it('returns null default when no row exists and no default provided', function (): void {
    expect(LinguaSetting::get('nonexistent.key'))->toBeNull();
});

// ---------------------------------------------------------------------------
// set() + get() — bool type
// ---------------------------------------------------------------------------

it('stores and retrieves a boolean true value', function (): void {
    LinguaSetting::set(LinguaSetting::KEY_SHOW_FLAGS, true);

    expect(LinguaSetting::get(LinguaSetting::KEY_SHOW_FLAGS))->toBeTrue();
});

it('stores and retrieves a boolean false value', function (): void {
    LinguaSetting::set(LinguaSetting::KEY_SHOW_FLAGS, false);

    expect(LinguaSetting::get(LinguaSetting::KEY_SHOW_FLAGS))->toBeFalse();
});

it('persists the type as bool for boolean values', function (): void {
    LinguaSetting::set(LinguaSetting::KEY_SHOW_FLAGS, true);

    $row = LinguaSetting::where('key', LinguaSetting::KEY_SHOW_FLAGS)->first();
    expect($row->type)->toBe('bool');
    expect($row->value)->toBe('1');
});

// ---------------------------------------------------------------------------
// set() + get() — string type
// ---------------------------------------------------------------------------

it('stores and retrieves a string value', function (): void {
    LinguaSetting::set(LinguaSetting::KEY_SELECTOR_MODE, 'modal');

    expect(LinguaSetting::get(LinguaSetting::KEY_SELECTOR_MODE))->toBe('modal');
});

it('persists the type as string for string values', function (): void {
    LinguaSetting::set(LinguaSetting::KEY_SELECTOR_MODE, 'sidebar');

    $row = LinguaSetting::where('key', LinguaSetting::KEY_SELECTOR_MODE)->first();
    expect($row->type)->toBe('string');
});

// ---------------------------------------------------------------------------
// set() + get() — int type
// ---------------------------------------------------------------------------

it('stores and retrieves an integer value', function (): void {
    LinguaSetting::set('some.integer', 42);

    expect(LinguaSetting::get('some.integer'))->toBe(42);
});

it('persists the type as int for integer values', function (): void {
    LinguaSetting::set('some.integer', 42);

    $row = LinguaSetting::where('key', 'some.integer')->first();
    expect($row->type)->toBe('int');
});

// ---------------------------------------------------------------------------
// set() + get() — json type
// ---------------------------------------------------------------------------

it('stores and retrieves an array value as json', function (): void {
    LinguaSetting::set('some.array', ['a' => 1, 'b' => 2]);

    expect(LinguaSetting::get('some.array'))->toBe(['a' => 1, 'b' => 2]);
});

it('persists the type as json for array values', function (): void {
    LinguaSetting::set('some.array', ['x' => true]);

    $row = LinguaSetting::where('key', 'some.array')->first();
    expect($row->type)->toBe('json');
});

// ---------------------------------------------------------------------------
// updateOrCreate behaviour
// ---------------------------------------------------------------------------

it('overwrites an existing setting on subsequent set() calls', function (): void {
    LinguaSetting::set(LinguaSetting::KEY_SHOW_FLAGS, true);
    LinguaSetting::set(LinguaSetting::KEY_SHOW_FLAGS, false);

    expect(LinguaSetting::get(LinguaSetting::KEY_SHOW_FLAGS))->toBeFalse();
    expect(LinguaSetting::where('key', LinguaSetting::KEY_SHOW_FLAGS)->count())->toBe(1);
});

// ---------------------------------------------------------------------------
// SelectorMode validation
// ---------------------------------------------------------------------------

it('accepts all valid SelectorMode values for selector.mode', function (SelectorMode $mode): void {
    LinguaSetting::set(LinguaSetting::KEY_SELECTOR_MODE, $mode->value);

    expect(LinguaSetting::get(LinguaSetting::KEY_SELECTOR_MODE))->toBe($mode->value);
})->with(SelectorMode::cases());

it('throws an exception for an invalid selector.mode value', function (): void {
    expect(fn () => LinguaSetting::set(LinguaSetting::KEY_SELECTOR_MODE, 'invalid'))
        ->toThrow(InvalidArgumentException::class);
});
