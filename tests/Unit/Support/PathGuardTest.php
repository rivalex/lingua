<?php

declare(strict_types=1);

use Rivalex\Lingua\Support\PathGuard;

test('safe segments pass without exception', function (string $segment): void {
    expect(fn () => PathGuard::assertSafeSegment($segment))->not->toThrow(InvalidArgumentException::class);
})->with(['en', 'fr-CA', 'fr_CA', 'validation', 'vendor-name', 'my_group', 'abc123']);

test('empty segment throws', function (): void {
    expect(fn () => PathGuard::assertSafeSegment(''))->toThrow(InvalidArgumentException::class);
});

test('forward slash throws', function (): void {
    expect(fn () => PathGuard::assertSafeSegment('en/malicious'))->toThrow(InvalidArgumentException::class);
});

test('backslash throws', function (): void {
    expect(fn () => PathGuard::assertSafeSegment('en\\malicious'))->toThrow(InvalidArgumentException::class);
});

test('dotdot traversal throws', function (): void {
    expect(fn () => PathGuard::assertSafeSegment('../etc/passwd'))->toThrow(InvalidArgumentException::class);
});

test('bare dotdot throws', function (): void {
    expect(fn () => PathGuard::assertSafeSegment('..'))->toThrow(InvalidArgumentException::class);
});

test('null byte throws', function (): void {
    expect(fn () => PathGuard::assertSafeSegment("en\0x"))->toThrow(InvalidArgumentException::class);
});

test('context is included in exception message', function (): void {
    expect(fn () => PathGuard::assertSafeSegment('', 'locale'))
        ->toThrow(InvalidArgumentException::class, '(locale)');
});
