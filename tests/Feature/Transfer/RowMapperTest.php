<?php

declare(strict_types=1);

use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Support\TranslationLine;
use Rivalex\Lingua\Transfer\Enums\TransferFilter;
use Rivalex\Lingua\Transfer\Enums\TransferScope;
use Rivalex\Lingua\Transfer\ParsedRow;
use Rivalex\Lingua\Transfer\RowMapper;
use Rivalex\Lingua\Transfer\TransferSchema;

// Helper: build a TranslationLine for testing
function makeLine(
    string $group,
    string $key,
    array $text = [],
    bool $isVendor = false,
    ?string $vendor = null,
    LinguaType $type = LinguaType::text,
): TranslationLine {
    $groupKey = $isVendor && $vendor
        ? "{$vendor}::{$group}.{$key}"
        : "{$group}.{$key}";

    return new TranslationLine(
        group: $group,
        key: $key,
        groupKey: $groupKey,
        type: $type,
        text: $text,
        isVendor: $isVendor,
        vendor: $vendor,
    );
}

// ── lineToRow ─────────────────────────────────────────────────────────────────

test('lineToRow bilingual: _key is group.key without vendor prefix, _vendor is empty', function (): void {
    $mapper = new RowMapper;
    $line = makeLine('messages', 'hello', ['en' => 'Hello', 'it' => 'Ciao']);

    $row = $mapper->lineToRow($line, 'en', TransferScope::bilingual, 'it', TransferFilter::all, 'en');

    expect($row)->not->toBeNull()
        ->and($row[TransferSchema::KEY])->toBe('messages.hello')
        ->and($row[TransferSchema::VENDOR])->toBe('');
});

test('lineToRow bilingual vendor row: _key is group.key (stripped), _vendor holds vendor name', function (): void {
    $mapper = new RowMapper;
    $line = makeLine('pagination', 'next', ['en' => 'Next', 'it' => 'Successivo'], true, 'spatie');

    $row = $mapper->lineToRow($line, 'en', TransferScope::bilingual, 'it', TransferFilter::all, 'en');

    expect($row)->not->toBeNull()
        ->and($row[TransferSchema::KEY])->toBe('pagination.next')  // stripped of "spatie::"
        ->and($row[TransferSchema::VENDOR])->toBe('spatie');
});

test('lineToRow multiLocale: all locale columns present in text map', function (): void {
    $mapper = new RowMapper;
    $line = makeLine('auth', 'login', ['en' => 'Login', 'it' => 'Accedi', 'fr' => 'Connexion']);

    $row = $mapper->lineToRow($line, 'en', TransferScope::multiLocale, null, TransferFilter::all, 'en');

    expect($row)->not->toBeNull()
        ->and($row)->toHaveKey(TransferSchema::KEY)
        ->and($row)->toHaveKey(TransferSchema::targetHeader('en'))
        ->and($row)->toHaveKey(TransferSchema::targetHeader('it'))
        ->and($row)->toHaveKey(TransferSchema::targetHeader('fr'));
});

test('lineToRow onlyMissing: skips rows where target locale has a value', function (): void {
    $mapper = new RowMapper;
    $line = makeLine('messages', 'hello', ['en' => 'Hello', 'it' => 'Ciao']);

    // 'it' already has a value — should be skipped
    $row = $mapper->lineToRow($line, 'en', TransferScope::bilingual, 'it', TransferFilter::onlyMissing, 'en');

    expect($row)->toBeNull();
});

test('lineToRow onlyMissing: includes rows where target locale is empty', function (): void {
    $mapper = new RowMapper;
    $line = makeLine('messages', 'bye', ['en' => 'Bye', 'it' => '']);

    $row = $mapper->lineToRow($line, 'en', TransferScope::bilingual, 'it', TransferFilter::onlyMissing, 'en');

    expect($row)->not->toBeNull()
        ->and($row[TransferSchema::KEY])->toBe('messages.bye');
});

test('lineToRow onlyMissing: includes rows where target locale is absent', function (): void {
    $mapper = new RowMapper;
    $line = makeLine('messages', 'bye', ['en' => 'Bye']); // no 'it' key at all

    $row = $mapper->lineToRow($line, 'en', TransferScope::bilingual, 'it', TransferFilter::onlyMissing, 'en');

    expect($row)->not->toBeNull();
});

test('lineToRow bilingual source==target: source and target columns both show default locale value', function (): void {
    $mapper = new RowMapper;
    $line = makeLine('messages', 'hello', ['en' => 'Hello']);

    // Exporting default locale 'en' as both default and target
    $row = $mapper->lineToRow($line, 'en', TransferScope::bilingual, 'en', TransferFilter::all, 'en');

    expect($row)->not->toBeNull();
    $sourceHeader = TransferSchema::sourceHeader('en');
    $targetHeader = TransferSchema::targetHeader('en');
    expect($row[$sourceHeader])->toBe('Hello')
        ->and($row[$targetHeader])->toBe('Hello');
});

// ── parseRow + resolveIdentity ────────────────────────────────────────────────

test('parseRow + resolveIdentity: existing key matches existence index', function (): void {
    $mapper = new RowMapper;

    $existingLine = makeLine('auth', 'login', ['en' => 'Login', 'it' => '']);
    $existenceIndex = ['auth.login' => $existingLine];

    $headers = [TransferSchema::KEY, TransferSchema::TYPE, TransferSchema::targetHeader('it'), TransferSchema::VENDOR];
    $row = array_combine($headers, ['auth.login', 'text', 'Accedi', '']);

    $parsed = $mapper->parseRow($row, $headers, 'it');
    $resolved = $mapper->resolveIdentity($parsed, $existenceIndex);

    expect($resolved->group)->toBe('auth')
        ->and($resolved->key)->toBe('login')
        ->and($resolved->isVendor)->toBeFalse()
        ->and($resolved->targetValue)->toBe('Accedi');
});

test('parseRow + resolveIdentity: new key with no dot becomes group=single, key=rawKey', function (): void {
    $mapper = new RowMapper;

    $headers = [TransferSchema::KEY, TransferSchema::TYPE, TransferSchema::targetHeader('it'), TransferSchema::VENDOR];
    $row = array_combine($headers, ['welcome', 'text', 'Benvenuto', '']);

    $parsed = $mapper->parseRow($row, $headers, 'it');
    $resolved = $mapper->resolveIdentity($parsed, []);

    expect($resolved->group)->toBe('single')
        ->and($resolved->key)->toBe('welcome');
});

test('parseRow + resolveIdentity: new key auth.login splits correctly', function (): void {
    $mapper = new RowMapper;

    $headers = [TransferSchema::KEY, TransferSchema::TYPE, TransferSchema::targetHeader('it'), TransferSchema::VENDOR];
    $row = array_combine($headers, ['auth.login', 'text', 'Accedi', '']);

    $parsed = $mapper->parseRow($row, $headers, 'it');
    $resolved = $mapper->resolveIdentity($parsed, []);

    expect($resolved->group)->toBe('auth')
        ->and($resolved->key)->toBe('login');
});

test('parseRow + resolveIdentity: nested auth.form.email splits on first dot only', function (): void {
    $mapper = new RowMapper;

    $headers = [TransferSchema::KEY, TransferSchema::TYPE, TransferSchema::targetHeader('it'), TransferSchema::VENDOR];
    $row = array_combine($headers, ['auth.form.email', 'text', 'Email', '']);

    $parsed = $mapper->parseRow($row, $headers, 'it');
    $resolved = $mapper->resolveIdentity($parsed, []);

    expect($resolved->group)->toBe('auth')
        ->and($resolved->key)->toBe('form.email');
});

test('parseRow + resolveIdentity: vendor row with _vendor column resolves as vendor', function (): void {
    $mapper = new RowMapper;

    $existingLine = makeLine('pagination', 'next', ['en' => 'Next'], true, 'spatie');
    $existenceIndex = ['spatie::pagination.next' => $existingLine];

    $headers = [TransferSchema::KEY, TransferSchema::TYPE, TransferSchema::targetHeader('it'), TransferSchema::VENDOR];
    $row = array_combine($headers, ['pagination.next', 'text', 'Successivo', 'spatie']);

    $parsed = $mapper->parseRow($row, $headers, 'it');
    $resolved = $mapper->resolveIdentity($parsed, $existenceIndex);

    expect($resolved->isVendor)->toBeTrue()
        ->and($resolved->vendorName)->toBe('spatie')
        ->and($resolved->group)->toBe('pagination')
        ->and($resolved->key)->toBe('next');
});

// ── Security: reject wildcard / null-byte import keys (F6) ─────────────────────

test('resolveIdentity rejects wildcard key — returns empty rawKey so the row is skipped', function (): void {
    $mapper = new RowMapper;

    $parsed = new ParsedRow(
        rawKey: 'auth.*.password',
        vendor: '',
        typeRaw: 'text',
        targetValue: 'pwned',
    );

    $resolved = $mapper->resolveIdentity($parsed, []);

    expect($resolved->rawKey)->toBe('')
        ->and($resolved->key)->toBeNull()
        ->and($resolved->group)->toBeNull();
});

test('resolveIdentity rejects null-byte key — returns empty rawKey so the row is skipped', function (): void {
    $mapper = new RowMapper;

    $parsed = new ParsedRow(
        rawKey: "auth.\0evil",
        vendor: '',
        typeRaw: 'text',
        targetValue: 'pwned',
    );

    $resolved = $mapper->resolveIdentity($parsed, []);

    expect($resolved->rawKey)->toBe('')
        ->and($resolved->key)->toBeNull()
        ->and($resolved->group)->toBeNull();
});

test('resolveIdentity rejects wildcard group — returns empty rawKey so the row is skipped', function (): void {
    $mapper = new RowMapper;

    $parsed = new ParsedRow(
        rawKey: '*.password',
        vendor: '',
        typeRaw: 'text',
        targetValue: 'pwned',
    );

    $resolved = $mapper->resolveIdentity($parsed, []);

    expect($resolved->rawKey)->toBe('');
});

test('resolveIdentity accepts a normal new key unchanged', function (): void {
    $mapper = new RowMapper;

    $parsed = new ParsedRow(
        rawKey: 'auth.valid_key',
        vendor: '',
        typeRaw: 'text',
        targetValue: 'ok',
    );

    $resolved = $mapper->resolveIdentity($parsed, []);

    expect($resolved->rawKey)->toBe('auth.valid_key')
        ->and($resolved->group)->toBe('auth')
        ->and($resolved->key)->toBe('valid_key');
});

// ── Regression: source==target locale collision ───────────────────────────────

test('parseRow bilingual source==target: reads target column, not source column', function (): void {
    // Reproduces the bug where a bilingual export with source locale == target locale
    // (e.g. en→en) caused findLocaleValue() to match the source column first because
    // "en - English (source)" starts_with "en - " just like "en - English".
    $mapper = new RowMapper;

    $headers = [
        TransferSchema::KEY,
        TransferSchema::TYPE,
        TransferSchema::sourceHeader('en'),   // "en - English (source)"
        TransferSchema::targetHeader('en'),   // "en - English"
        TransferSchema::VENDOR,
    ];
    $row = array_combine($headers, [
        'http-statuses.4',
        'text',
        'OK',      // source column — must NOT be returned
        'OKOKOK',  // target column — MUST be returned
        '',
    ]);

    $parsed = $mapper->parseRow($row, $headers, 'en');

    expect($parsed->targetValue)->toBe('OKOKOK');
});

// ── Regression: bilingual single-candidate fallback (regional locale mismatch) ──

test('parseRow bilingual: regional locale mismatch still reads the single target column', function (): void {
    // Reproduces the bug: user selects "it_IT" at import time but the header is "it - Italian".
    // None of the three exact/prefix lookups match "it_IT", so the old code returned '' and
    // every row was skipped with reason "empty target value".
    // The single-candidate fallback must recover and return the column value.
    $mapper = new RowMapper;

    $headers = [
        TransferSchema::KEY,
        TransferSchema::TYPE,
        TransferSchema::sourceHeader('en'),  // "en - English (source)"
        TransferSchema::targetHeader('it'),  // "it - Italian"
        TransferSchema::VENDOR,
    ];
    $row = array_combine($headers, ['http-statuses.4', 'text', 'OK', 'OKXXX', '']);

    $parsed = $mapper->parseRow($row, $headers, 'it_IT');

    expect($parsed->targetValue)->toBe('OKXXX');
});

test('parseRow multiLocale: ambiguous locale columns return empty string', function (): void {
    // Two non-meta, non-source columns: the fallback must NOT guess — returns ''.
    $mapper = new RowMapper;

    $headers = [
        TransferSchema::KEY,
        TransferSchema::TYPE,
        TransferSchema::targetHeader('it'),  // "it - Italian"
        TransferSchema::targetHeader('fr'),  // "fr - French"
        TransferSchema::VENDOR,
    ];
    $row = array_combine($headers, ['auth.login', 'text', 'Accedi', 'Connexion', '']);

    $parsed = $mapper->parseRow($row, $headers, 'de');

    expect($parsed->targetValue)->toBe('');
});

test('parseRow bilingual: single-candidate fallback resolves when target locale code does not match column header', function (): void {
    // Reproduces the bug where importing a bilingual file with target locale "it" but the
    // Language row in the DB stores "it_IT" (or the user selects "it_IT") caused every row
    // to be classified as "empty target value" because none of the three string-match paths
    // matched "it_IT" against the header "it - Italian".
    $mapper = new RowMapper;

    $headers = [
        TransferSchema::KEY,
        TransferSchema::TYPE,
        TransferSchema::sourceHeader('en'),   // "en - English (source)"
        TransferSchema::targetHeader('it'),   // "it - Italian"
        TransferSchema::VENDOR,
    ];
    $row = array_combine($headers, [
        'http-statuses.4',
        'text',
        'OK',     // source column — must NOT be returned
        'OKXXX',  // target column — MUST be returned via fallback
        '',
    ]);

    // Simulate a target locale that doesn't string-match "it - Italian"
    $parsed = $mapper->parseRow($row, $headers, 'it_IT');

    expect($parsed->targetValue)->toBe('OKXXX');
});

test('parseRow multiLocale: two data columns + mismatched locale stays ambiguous and returns empty string', function (): void {
    // Safety check: when a multiLocale file has ≥2 data columns and none match the
    // selected locale, the fallback must NOT guess — it must return '' so the row is
    // skipped (correct behaviour; ambiguous which column is intended).
    $mapper = new RowMapper;

    $headers = [
        TransferSchema::KEY,
        TransferSchema::TYPE,
        TransferSchema::targetHeader('it'),   // "it - Italian"
        TransferSchema::targetHeader('fr'),   // "fr - French"
        TransferSchema::VENDOR,
    ];
    $row = array_combine($headers, [
        'auth.login',
        'text',
        'Accedi',
        'Connexion',
        '',
    ]);

    // "de" matches neither column — two candidates, fallback must not guess
    $parsed = $mapper->parseRow($row, $headers, 'de');

    expect($parsed->targetValue)->toBe('');
});
