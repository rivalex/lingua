<?php

declare(strict_types=1);

use Rivalex\Lingua\Exceptions\VendorTranslationProtectedException;

it('uses the default message', function (): void {
    $e = new VendorTranslationProtectedException;

    expect($e->getMessage())->toBe('Vendor translations cannot be deleted.');
});

it('accepts a custom message', function (): void {
    $e = new VendorTranslationProtectedException('Custom error.');

    expect($e->getMessage())->toBe('Custom error.');
});

it('extends RuntimeException', function (): void {
    expect(new VendorTranslationProtectedException)->toBeInstanceOf(RuntimeException::class);
});
