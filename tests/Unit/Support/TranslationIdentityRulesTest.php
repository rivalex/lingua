<?php

declare(strict_types=1);

use Rivalex\Lingua\Support\TranslationIdentityRules;

it('accepts well-formed identities', function (string $group, string $key): void {
    expect(TranslationIdentityRules::groupError($group))->toBeNull()
        ->and(TranslationIdentityRules::keyError($group, $key))->toBeNull();
})->with([
    'plain group' => ['auth', 'failed'],
    'hyphen group' => ['my-group', 'title'],
    'single sentence key' => ['single', 'Welcome back. Please log in.'],
    'nested key' => ['app', 'nested.key'],
]);

it('rejects malformed keys outside the single group', function (string $key): void {
    expect(TranslationIdentityRules::keyError('app', $key))->toBeString();
})->with([
    'wildcard' => ['*'],
    'wildcard segment' => ['*.x'],
    'empty middle segment' => ['a..b'],
    'leading dot' => ['.a'],
    'trailing dot' => ['a.'],
    'null byte' => ["a\0b"],
    'newline' => ["a\nb"],
    'empty' => [''],
    'blank' => ['   '],
    'too long' => [str_repeat('a', 1001)],
]);

it('rejects control characters and wildcards in single keys too', function (string $key, bool $rejected): void {
    expect(TranslationIdentityRules::keyError('single', $key) !== null)->toBe($rejected);
})->with([
    'null byte' => ["a\0b", true],
    'empty' => ['', true],
    'wildcard is a literal sentence' => ['5 * 3', false],
]);

it('rejects malformed group and vendor names', function (string $name): void {
    expect(TranslationIdentityRules::groupError($name))->toBeString()
        ->and(TranslationIdentityRules::vendorError($name))->toBeString();
})->with([
    'wildcard' => ['*'],
    'traversal' => ['../x'],
    'namespace separator' => ['a::b'],
    'space' => ['a b'],
    'pipe' => ['a|b'],
    'empty' => [''],
    'too long' => [str_repeat('a', 65)],
]);

it('accepts the literal single as group but not as vendor exception', function (): void {
    expect(TranslationIdentityRules::groupError('single'))->toBeNull();
});

it('rejects a malformed locale', function (): void {
    expect(TranslationIdentityRules::localeError('../en'))->toBeString()
        ->and(TranslationIdentityRules::localeError('pt_BR'))->toBeNull()
        ->and(TranslationIdentityRules::localeError('en'))->toBeNull();
});

it('assertValid throws a prefixed exception on the first error', function (): void {
    expect(fn () => TranslationIdentityRules::assertValid('../x', 'k', null, 'en'))
        ->toThrow(InvalidArgumentException::class, '[Lingua]');
    expect(fn () => TranslationIdentityRules::assertValid('app', '*', null, 'en'))
        ->toThrow(InvalidArgumentException::class, '[Lingua]');
    expect(fn () => TranslationIdentityRules::assertValid('app', 'k', 'a::b', 'en'))
        ->toThrow(InvalidArgumentException::class, '[Lingua]');
    expect(fn () => TranslationIdentityRules::assertValid('app', 'k', null, '../en'))
        ->toThrow(InvalidArgumentException::class, '[Lingua]');

    TranslationIdentityRules::assertValid('app', 'k', 'acme', 'en');
    TranslationIdentityRules::assertValid('app', 'k', null, null);
    expect(true)->toBeTrue();
});

it('rejects a wildcard dot segment in single keys', function (string $key): void {
    expect(TranslationIdentityRules::keyError('single', $key))->toBe('The key must not contain a "*" segment.');
})->with([
    'bare wildcard' => ['*'],
    'trailing wildcard segment' => ['a.*'],
    'leading wildcard segment' => ['*.b'],
    'middle wildcard segment' => ['a.*.b'],
]);

it('accepts a literal asterisk inside a single key sentence', function (string $key): void {
    expect(TranslationIdentityRules::keyError('single', $key))->toBeNull();
})->with([
    'multiplication' => ['5 * 3'],
    'label' => ['Note: *required'],
    'inside a word' => ['a*b'],
]);

it('rejects a pipe in any key, because it separates the parts of a translation identity', function (string $group): void {
    expect(TranslationIdentityRules::keyError($group, 'a|b'))->toBe('The key must not contain the "|" character.');
})->with(['app', 'single']);

it('rejects a key that is a dot-prefix of an existing key or has one as its prefix', function (string $key, array $existing): void {
    expect(TranslationIdentityRules::collisionError('auth', $key, $existing))->toBeString();
})->with([
    'child of an existing key' => ['failed.x', ['failed']],
    'parent of an existing key' => ['failed', ['failed.x']],
    'deeper child' => ['a.b.c', ['x', 'a.b']],
]);

it('accepts a key that does not collide with the existing keys', function (string $group, string $key, array $existing): void {
    expect(TranslationIdentityRules::collisionError($group, $key, $existing))->toBeNull();
})->with([
    'shared leading characters only' => ['auth', 'failed2', ['failed']],
    'the same key is not a collision' => ['auth', 'failed', ['failed']],
    'sibling' => ['auth', 'a.c', ['a.b']],
    'single is literal' => ['single', 'Login.x', ['Login']],
    'single is literal the other way' => ['single', 'Login', ['Login.x']],
]);

it('rejects a key that differs from an existing key only by case, in every group', function (string $group, string $key, array $existing, string $expected): void {
    expect(TranslationIdentityRules::collisionError($group, $key, $existing))
        ->toBe('The key differs only by case from the existing key "'.$expected.'".');
})->with([
    'dotted group' => ['msg', 'msg.Same', ['msg.same'], 'msg.same'],
    'plain key' => ['msg', 'Same', ['same'], 'same'],
    'single group' => ['single', 'Hello', ['hello'], 'hello'],
    'multibyte' => ['single', 'Über', ['über'], 'über'],
]);

it('accepts the exact same key and a key that differs by more than case', function (string $group, string $key, array $existing): void {
    expect(TranslationIdentityRules::collisionError($group, $key, $existing))->toBeNull();
})->with([
    'exact in dotted group' => ['msg', 'msg.same', ['msg.same']],
    'exact in single' => ['single', 'Hello', ['Hello']],
    'different word' => ['single', 'Hello', ['Help']],
]);

it('rejects a group that differs from an existing one only by case', function (): void {
    expect(TranslationIdentityRules::groupCaseError('Auth', ['auth', 'validation']))->toBeString()
        ->and(TranslationIdentityRules::groupCaseError('Single', ['single']))->toBeString();
});

it('accepts a group that differs from the existing ones by more than case', function (): void {
    expect(TranslationIdentityRules::groupCaseError('auth2', ['auth']))->toBeNull()
        ->and(TranslationIdentityRules::groupCaseError('auth', ['auth']))->toBeNull()
        ->and(TranslationIdentityRules::groupCaseError('auth', []))->toBeNull();
});

it('rejects a trailing newline in a locale, a group and a vendor', function (): void {
    expect(TranslationIdentityRules::localeError("en\n"))->toBeString()
        ->and(TranslationIdentityRules::groupError("auth\n"))->toBeString()
        ->and(TranslationIdentityRules::vendorError("acme\n"))->toBeString()
        ->and(TranslationIdentityRules::localeError('en'))->toBeNull();
});

it('limits a key to the 300 characters of the column', function (): void {
    expect(TranslationIdentityRules::keyError('app', str_repeat('a', 301)))->toBeString()
        ->and(TranslationIdentityRules::keyError('app', str_repeat('a', 300)))->toBeNull();
});

it('rejects a key with leading, trailing or repeated whitespace, in every group', function (string $group, string $key, bool $rejected): void {
    expect(TranslationIdentityRules::keyError($group, $key) !== null)->toBe($rejected);
})->with([
    'repeated' => ['app', 'a  b', true],
    'leading' => ['app', ' a', true],
    'trailing' => ['app', 'a ', true],
    'repeated in single' => ['single', 'a  b', true],
    'single space' => ['app', 'a b', false],
    'single space in single' => ['single', 'a b', false],
]);
