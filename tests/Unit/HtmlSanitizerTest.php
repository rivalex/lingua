<?php

declare(strict_types=1);

use Rivalex\Lingua\Support\HtmlSanitizer;

test('keeps whitelisted formatting tags', function (): void {
    $html = '<p>Hello <strong>world</strong> and <em>everyone</em></p>';

    expect(HtmlSanitizer::sanitize($html))->toBe($html);
})->group('sanitizer');

test('removes script tags including their content', function (): void {
    $output = HtmlSanitizer::sanitize('<p>Safe</p><script>alert(1)</script>');

    expect($output)->toBe('<p>Safe</p>')
        ->and($output)->not->toContain('alert');
})->group('sanitizer');

test('unwraps disallowed tags but keeps their text', function (): void {
    $output = HtmlSanitizer::sanitize('<p><article>inner text</article></p>');

    expect($output)->not->toContain('<article')
        ->and($output)->toContain('inner text');
})->group('sanitizer');

test('drops iframe elements entirely', function (): void {
    // libxml parses iframe content as CDATA; the sanitizer removes CDATA,
    // so the whole element (including fallback content) is discarded.
    expect(HtmlSanitizer::sanitize('<p><iframe src="https://evil.test">x</iframe></p>'))
        ->not->toContain('iframe')
        ->not->toContain('evil.test');
})->group('sanitizer');

test('strips event handler attributes from allowed tags', function (): void {
    $output = HtmlSanitizer::sanitize('<img src="https://example.com/x.png" onerror="alert(1)">');

    expect($output)->toContain('<img')
        ->and($output)->toContain('src="https://example.com/x.png"')
        ->and($output)->not->toContain('onerror');
})->group('sanitizer');

test('removes javascript URIs from href', function (): void {
    $output = HtmlSanitizer::sanitize('<a href="javascript:alert(1)">click</a>');

    expect($output)->toContain('<a')
        ->and($output)->not->toContain('javascript');
})->group('sanitizer');

test('removes obfuscated javascript URIs', function (): void {
    $output = HtmlSanitizer::sanitize("<a href=\"java\tscript:alert(1)\">x</a><a href=' javascript:alert(2)'>y</a>");

    expect($output)->not->toContain('script:alert');
})->group('sanitizer');

test('removes data URIs from img src', function (): void {
    $output = HtmlSanitizer::sanitize('<img src="data:text/html;base64,PHNjcmlwdD4=">');

    expect($output)->not->toContain('data:');
})->group('sanitizer');

test('keeps relative and same-page URIs', function (): void {
    $output = HtmlSanitizer::sanitize('<a href="/docs/page#anchor">docs</a>');

    expect($output)->toContain('href="/docs/page#anchor"');
})->group('sanitizer');

test('keeps http https and mailto URIs', function (): void {
    $output = HtmlSanitizer::sanitize(
        '<a href="https://example.com">a</a><a href="http://example.com">b</a><a href="mailto:x@example.com">c</a>'
    );

    expect($output)->toContain('https://example.com')
        ->and($output)->toContain('http://example.com')
        ->and($output)->toContain('mailto:x@example.com');
})->group('sanitizer');

test('removes style attributes and comments', function (): void {
    $output = HtmlSanitizer::sanitize('<p style="background:url(javascript:1)">x</p><!-- secret -->');

    expect($output)->not->toContain('style=')
        ->and($output)->not->toContain('secret');
})->group('sanitizer');

test('returns empty string for blank input', function (): void {
    expect(HtmlSanitizer::sanitize(''))->toBe('')
        ->and(HtmlSanitizer::sanitize('   '))->toBe('');
})->group('sanitizer');

test('preserves unicode content', function (): void {
    $output = HtmlSanitizer::sanitize('<p>Città è già perché 日本語</p>');

    expect($output)->toContain('Città è già perché 日本語');
})->group('sanitizer');
