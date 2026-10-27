<?php

declare(strict_types=1);

use Rivalex\Lingua\Support\DottedKeyArray;

it('sets a value into an existing key that itself contains dots and a wildcard', function (): void {
    $array = ['custom' => ['items.*.name' => ['required' => 'FILE']]];

    DottedKeyArray::set($array, 'custom.items.*.name.required', 'NEW');

    expect($array)->toBe(['custom' => ['items.*.name' => ['required' => 'NEW']]]);
});

it('sets a value into an existing nested shape', function (): void {
    $array = ['attributes' => ['address' => ['street' => 'Via', 'city' => 'Citta']]];

    DottedKeyArray::set($array, 'attributes.address.street', 'Strada');

    expect($array)->toBe(['attributes' => ['address' => ['street' => 'Strada', 'city' => 'Citta']]]);
});

it('expands a new dotted key into a nested path', function (): void {
    $array = ['other' => 'x'];

    DottedKeyArray::set($array, 'attributes.address.street', 'Street');

    expect($array)->toBe(['other' => 'x', 'attributes' => ['address' => ['street' => 'Street']]]);
});

it('writes a key containing a wildcard literally', function (): void {
    $array = [];

    DottedKeyArray::set($array, 'custom.items.*.name', 'Name');

    expect($array)->toBe(['custom' => ['items' => ['*' => ['name' => 'Name']]]]);
});

it('reports whether a key exists in the shape or as a nested path', function (): void {
    $array = ['custom' => ['items.*.name' => ['required' => 'FILE']], 'plain' => 'x'];

    expect(DottedKeyArray::has($array, 'custom.items.*.name.required'))->toBeTrue()
        ->and(DottedKeyArray::has($array, 'plain'))->toBeTrue()
        ->and(DottedKeyArray::has($array, 'custom.items.*.name.min'))->toBeFalse()
        ->and(DottedKeyArray::has($array, 'missing.key'))->toBeFalse();
});

it('forgets the leaf of an existing shaped key', function (): void {
    $array = ['custom' => ['items.*.name' => ['required' => 'FILE', 'min' => 'MIN']]];

    DottedKeyArray::forget($array, 'custom.items.*.name.required');

    expect($array)->toBe(['custom' => ['items.*.name' => ['min' => 'MIN']]]);
});

it('does nothing when forgetting a missing key', function (): void {
    $array = ['custom' => ['items.*.name' => ['required' => 'FILE']]];

    DottedKeyArray::forget($array, 'custom.items.*.name.min');
    DottedKeyArray::forget($array, 'nothing.here');

    expect($array)->toBe(['custom' => ['items.*.name' => ['required' => 'FILE']]]);
});
