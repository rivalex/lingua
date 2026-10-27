<?php

declare(strict_types=1);

use Rivalex\Lingua\Support\HtmlSanitizer;

test('protocol-relative img src is removed', function (): void {
    expect(HtmlSanitizer::sanitize('<img src="//evil.example/p.gif">'))->not->toContain('src');
});

test('backslash-led href is removed', function (): void {
    expect(HtmlSanitizer::sanitize('<a href="\\\\evil.example">x</a>'))->not->toContain('href');
});

test('whitespace-obfuscated protocol-relative href is removed', function (string $uri): void {
    expect(HtmlSanitizer::sanitize('<a href="'.$uri.'">x</a>'))->not->toContain('href');
})->with([
    'tab' => "/\t/evil.example",
    'space' => '/ /evil.example',
    'newline entity' => '/&#10;/evil.example',
]);

test('slash-backslash href is removed', function (string $uri): void {
    expect(HtmlSanitizer::sanitize('<a href="'.$uri.'">x</a>'))->not->toContain('href');
})->with([
    'literal' => '/\\evil.example',
    'backslash entity' => '/&#92;evil.example',
    'tab then backslash' => "/\t\\evil.example",
]);

test('relative path and https href are kept', function (): void {
    expect(HtmlSanitizer::sanitize('<a href="/path">x</a>'))->toContain('href="/path"');
    expect(HtmlSanitizer::sanitize('<a href="https://example.com/a">x</a>'))->toContain('href="https://example.com/a"');
});

test('a bare replacement placeholder href is removed', function (): void {
    expect(HtmlSanitizer::sanitize('Click <a href=":url">here</a>'))->toBe('Click <a>here</a>');
});

test('a link with a fixed prefix before the placeholder is kept', function (string $uri): void {
    expect(HtmlSanitizer::sanitize('<a href="'.$uri.'">x</a>'))->toContain('href="'.$uri.'"');
})->with([
    'path prefix' => ['/:path'],
    'absolute prefix' => ['https://example.com/:path'],
]);

test('a placeholder followed by a path or glued to a scheme is removed', function (string $uri): void {
    expect(HtmlSanitizer::sanitize('<a href="'.$uri.'">x</a>'))->not->toContain('href');
})->with([
    'placeholder with path' => [':url/x'],
    'scheme glued to placeholder' => ['javascript::url'],
]);

test('a placeholder with surrounding whitespace is removed', function (): void {
    expect(HtmlSanitizer::sanitize('<a href=" :url">x</a>'))->not->toContain('href');
});

test('a numeric or named reference that hides the scheme loses its href', function (string $uri): void {
    expect(HtmlSanitizer::sanitize('<a href="'.$uri.'">x</a>'))->not->toContain('href');
})->with([
    'numeric reference without semicolon' => ['javascript&#58alert(1)'],
    'named colon reference' => ['javascript&colon;alert(1)'],
]);
