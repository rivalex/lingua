<?php

declare(strict_types=1);

use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Support\TranslationLine;
use Rivalex\Lingua\Transfer\Enums\TransferFilter;
use Rivalex\Lingua\Transfer\Enums\TransferScope;
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
