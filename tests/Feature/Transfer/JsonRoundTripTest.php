<?php

declare(strict_types=1);

use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Support\TranslationLine;
use Rivalex\Lingua\Transfer\Enums\TransferFilter;
use Rivalex\Lingua\Transfer\Enums\TransferScope;
use Rivalex\Lingua\Transfer\Format\JsonReader;
use Rivalex\Lingua\Transfer\Format\JsonWriter;
use Rivalex\Lingua\Transfer\RowMapper;

function makeJsonLine(
    string $group,
    string $key,
    array $text,
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

test('json lossless round-trip: type preserved', function (): void {
    $mapper = new RowMapper;
    $line = makeJsonLine('messages', 'hello', ['en' => 'Hello', 'it' => 'Ciao'], type: LinguaType::html);

    $row = $mapper->lineToRow($line, 'en', TransferScope::jsonNative, null, TransferFilter::all, 'en');

    expect($row['type'])->toBe('html');
});

test('json lossless round-trip: isVendor and vendor preserved', function (): void {
    $mapper = new RowMapper;
    $line = makeJsonLine('pagination', 'next', ['en' => 'Next'], true, 'spatie');

    $row = $mapper->lineToRow($line, 'en', TransferScope::jsonNative, null, TransferFilter::all, 'en');

    expect($row['isVendor'])->toBeTrue()
        ->and($row['vendor'])->toBe('spatie');
});

test('json lossless round-trip: all locales preserved in text map', function (): void {
    $mapper = new RowMapper;
    $line = makeJsonLine('auth', 'login', ['en' => 'Login', 'it' => 'Accedi', 'fr' => 'Connexion']);

    $row = $mapper->lineToRow($line, 'en', TransferScope::jsonNative, null, TransferFilter::all, 'en');

    expect($row['text'])->toBe(['en' => 'Login', 'it' => 'Accedi', 'fr' => 'Connexion']);
});

test('json write→read round-trip: written file can be decoded back', function (): void {
    $mapper = new RowMapper;
    $lines = [
        makeJsonLine('auth', 'login', ['en' => 'Login', 'it' => 'Accedi'], type: LinguaType::text),
        makeJsonLine('messages', 'welcome', ['en' => 'Welcome'], type: LinguaType::html),
        makeJsonLine('pagination', 'next', ['en' => 'Next'], true, 'spatie', LinguaType::text),
    ];

    $rows = [];
    foreach ($lines as $line) {
        $row = $mapper->lineToRow($line, 'en', TransferScope::jsonNative, null, TransferFilter::all, 'en');
        if ($row !== null) {
            $rows[] = $row;
        }
    }

    $path = tempnam(sys_get_temp_dir(), 'lingua_json_test_');
    $writer = new JsonWriter;
    $writer->write($path, [], $rows);

    $contents = file_get_contents($path);
    @unlink($path);

    $decoded = json_decode($contents, true);

    expect($decoded)->toHaveCount(3)
        ->and($decoded[0]['group'])->toBe('auth')
        ->and($decoded[0]['key'])->toBe('login')
        ->and($decoded[0]['type'])->toBe('text')
        ->and($decoded[0]['text'])->toBe(['en' => 'Login', 'it' => 'Accedi'])
        ->and($decoded[1]['type'])->toBe('html')
        ->and($decoded[2]['isVendor'])->toBeTrue()
        ->and($decoded[2]['vendor'])->toBe('spatie');
});

test('json write→read via JsonReader: first row is headers, subsequent rows have values', function (): void {
    $mapper = new RowMapper;
    $line = makeJsonLine('auth', 'login', ['en' => 'Login', 'it' => 'Accedi']);
    $row = $mapper->lineToRow($line, 'en', TransferScope::jsonNative, null, TransferFilter::all, 'en');

    $path = tempnam(sys_get_temp_dir(), 'lingua_json_reader_');
    $writer = new JsonWriter;
    $writer->write($path, [], [$row]);

    $reader = new JsonReader;
    $all = iterator_to_array($reader->read($path));
    @unlink($path);

    // Row 0 = headers
    expect($all[0])->toContain('group')
        ->toContain('key')
        ->toContain('type');

    // Row 1 = values
    $headers = $all[0];
    $groupIdx = array_search('group', $headers);
    $keyIdx = array_search('key', $headers);
    expect($all[1][$groupIdx])->toBe('auth')
        ->and($all[1][$keyIdx])->toBe('login');
});

test('json mimeType and extension are correct', function (): void {
    $writer = new JsonWriter;
    expect($writer->mimeType())->toBe('application/json')
        ->and($writer->extension())->toBe('json');
});
