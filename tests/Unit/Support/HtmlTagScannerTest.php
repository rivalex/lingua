<?php

declare(strict_types=1);

use Rivalex\Lingua\Support\HtmlTagScanner;

it('returns the tokens of a start tag with quoted attributes and its end tag', function (): void {
    expect(HtmlTagScanner::scan('<a href="/x" title=\'y\'>z</a>'))->toBe([
        ['type' => 'start', 'name' => 'a', 'attributes' => [['href', '/x'], ['title', 'y']], 'selfClosing' => false],
        ['type' => 'end', 'name' => 'a', 'attributes' => [], 'selfClosing' => false],
    ]);
});

it('flags a self-closing tag', function (): void {
    expect(HtmlTagScanner::scan('<br/>'))->toBe([
        ['type' => 'start', 'name' => 'br', 'attributes' => [], 'selfClosing' => true],
    ]);
});

it('keeps a slash inside an unquoted value', function (): void {
    expect(HtmlTagScanner::scan('<img src=x alt=a/b>'))->toBe([
        ['type' => 'start', 'name' => 'img', 'attributes' => [['src', 'x'], ['alt', 'a/b']], 'selfClosing' => false],
    ]);
});

it('lowercases tag and attribute names', function (): void {
    expect(HtmlTagScanner::scan('<IMG SRC=x>'))->toBe([
        ['type' => 'start', 'name' => 'img', 'attributes' => [['src', 'x']], 'selfClosing' => false],
    ]);
});

it('returns no tags for plain text with a stray less-than', function (): void {
    expect(HtmlTagScanner::scan('a < b and I <3 Laravel'))->toBe([]);
});

it('returns null for a value that is not acceptable markup', function (string $value): void {
    expect(HtmlTagScanner::scan($value))->toBeNull();
})->with([
    'trailing less-than' => ['Showing <'],
    'comment' => ['<!-- c -->'],
    'processing instruction' => ['<?php ?>'],
    'end tag without a letter' => ['</ x>'],
    'unclosed tag' => ['<img src=x'],
    'stray quote' => ['<a "=">'],
]);
