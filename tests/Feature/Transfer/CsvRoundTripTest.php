<?php

declare(strict_types=1);

use Rivalex\Lingua\Transfer\Format\CsvReader;
use Rivalex\Lingua\Transfer\Format\CsvWriter;

// Helper: write rows to a temp CSV and read them back
function csvRoundTrip(array $headers, array $rows): array
{
    $path = tempnam(sys_get_temp_dir(), 'lingua_csv_test_');

    $writer = new CsvWriter;
    $writer->write($path, $headers, $rows);

    $reader = new CsvReader;
    $result = iterator_to_array($reader->read($path));

    @unlink($path);

    return $result;
}

test('csv round-trip: headers and rows survive write→read', function (): void {
    $headers = ['_key', '_type', 'en - English (source)', 'it - Italian', '_vendor'];
    $rows = [
        ['_key' => 'auth.login', '_type' => 'text', 'en - English (source)' => 'Login', 'it - Italian' => 'Accedi', '_vendor' => ''],
        ['_key' => 'auth.logout', '_type' => 'text', 'en - English (source)' => 'Logout', 'it - Italian' => 'Esci', '_vendor' => ''],
    ];

    $result = csvRoundTrip($headers, $rows);

    expect($result)->toHaveCount(3); // 1 header + 2 rows
    expect($result[0])->toBe($headers);
    expect($result[1])->toBe(['auth.login', 'text', 'Login', 'Accedi', '']);
    expect($result[2])->toBe(['auth.logout', 'text', 'Logout', 'Esci', '']);
});

test('csv formula injection: value starting with equals sign is prefixed with quote', function (): void {
    $headers = ['_key', '_type', 'value'];
    $rows = [
        ['_key' => 'test.key', '_type' => 'text', 'value' => '=SUM(A1)'],
    ];

    $result = csvRoundTrip($headers, $rows);

    // The stored value should have the leading quote stripped by fgetcsv
    // because we prefix with ' — fgetcsv will return it with the quote intact
    // Actually fgetcsv returns the raw string including the leading '
    expect($result[1][2])->toBe("'=SUM(A1)");
});

test('csv formula injection: value starting with plus sign is prefixed with quote', function (): void {
    $headers = ['_key', 'value'];
    $rows = [['_key' => 'k', 'value' => '+100']];

    $result = csvRoundTrip($headers, $rows);
    expect($result[1][1])->toBe("'+100");
});

test('csv formula injection: value starting with minus sign is prefixed with quote', function (): void {
    $headers = ['_key', 'value'];
    $rows = [['_key' => 'k', 'value' => '-1']];

    $result = csvRoundTrip($headers, $rows);
    expect($result[1][1])->toBe("'-1");
});

test('csv formula injection: value starting with at sign is prefixed with quote', function (): void {
    $headers = ['_key', 'value'];
    $rows = [['_key' => 'k', 'value' => '@SUM']];

    $result = csvRoundTrip($headers, $rows);
    expect($result[1][1])->toBe("'@SUM");
});

test('csv formula injection: normal text value is not prefixed', function (): void {
    $headers = ['_key', 'value'];
    $rows = [['_key' => 'k', 'value' => 'Hello world']];

    $result = csvRoundTrip($headers, $rows);
    expect($result[1][1])->toBe('Hello world');
});

test('csv round-trip: empty value survives', function (): void {
    $headers = ['_key', 'value'];
    $rows = [['_key' => 'k', 'value' => '']];

    $result = csvRoundTrip($headers, $rows);
    expect($result[1][1])->toBe('');
});

test('csv round-trip: unicode values survive', function (): void {
    $headers = ['_key', 'value'];
    $rows = [['_key' => 'k', 'value' => 'Héllo wörld — ñoño']];

    $result = csvRoundTrip($headers, $rows);
    expect($result[1][1])->toBe('Héllo wörld — ñoño');
});
