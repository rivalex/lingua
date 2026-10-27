<?php

declare(strict_types=1);

use Rivalex\Lingua\Models\Translation;

beforeEach(function (): void {
    Translation::create([
        'group' => 'isolation',
        'key' => 'greeting',
        'text' => ['en' => 'Hello from the app.'],
        'type' => 'text',
        'is_vendor' => false,
    ]);

    Translation::create([
        'group' => 'isolation',
        'key' => 'greeting',
        'text' => ['en' => 'Hello from acme.'],
        'type' => 'text',
        'is_vendor' => true,
        'vendor' => 'acme',
    ]);

    Translation::create([
        'group' => 'isolation',
        'key' => 'vendor_only',
        'text' => ['en' => 'Only in acme.'],
        'type' => 'text',
        'is_vendor' => true,
        'vendor' => 'acme',
    ]);
});

it('excludes vendor rows from an app group with the same name', function (): void {
    expect(Translation::getTranslationsForGroup('en', 'isolation'))
        ->toBe(['greeting' => 'Hello from the app.']);
});

it('keeps serving vendor rows through the vendor lookup', function (): void {
    expect(Translation::getVendorTranslationsForGroup('en', 'acme', 'isolation'))
        ->toBe(['greeting' => 'Hello from acme.', 'vendor_only' => 'Only in acme.']);
});
