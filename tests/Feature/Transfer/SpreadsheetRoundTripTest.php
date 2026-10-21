<?php

declare(strict_types=1);

use Rivalex\Lingua\Transfer\Format\FormatRegistry;
use Rivalex\Lingua\Transfer\Format\OdsReader;
use Rivalex\Lingua\Transfer\Format\OdsWriter;
use Rivalex\Lingua\Transfer\Format\SpreadsheetUnavailableException;
use Rivalex\Lingua\Transfer\Format\XlsxReader;
use Rivalex\Lingua\Transfer\Format\XlsxWriter;
use Rivalex\Lingua\Transfer\SpreadsheetSupport;

// ── Format registry degradation (always runs, regardless of OpenSpout) ────────

test('format registry always exposes csv and json', function (): void {
    $registry = new FormatRegistry;
    $formats = $registry->availableFormats();

    expect($formats)->toHaveKey('csv')
        ->toHaveKey('json');
});

test('format registry exposes xlsx and ods only when openspout is available', function (): void {
    $registry = new FormatRegistry;
    $formats = $registry->availableFormats();

    if (SpreadsheetSupport::available()) {
        expect($formats)->toHaveKey('xlsx')->toHaveKey('ods');
    } else {
        expect($formats)->not->toHaveKey('xlsx')->not->toHaveKey('ods');
    }
});

test('format registry writer throws SpreadsheetUnavailableException for xlsx when absent', function (): void {
    if (SpreadsheetSupport::available()) {
        $this->markTestSkipped('OpenSpout is installed; unavailable path not reachable.');
    }

    $registry = new FormatRegistry;
    expect(fn () => $registry->writer('xlsx'))->toThrow(SpreadsheetUnavailableException::class);
});

test('format registry reader throws SpreadsheetUnavailableException for ods when absent', function (): void {
    if (SpreadsheetSupport::available()) {
        $this->markTestSkipped('OpenSpout is installed; unavailable path not reachable.');
    }

    $registry = new FormatRegistry;
    expect(fn () => $registry->reader('ods'))->toThrow(SpreadsheetUnavailableException::class);
});

test('SpreadsheetUnavailableException message contains format name', function (): void {
    $e = new SpreadsheetUnavailableException('xlsx');
    expect($e->getMessage())->toContain('xlsx');
});

// ── XLSX round-trip (skipped when OpenSpout absent) ───────────────────────────

test('xlsx round-trip: headers and rows survive write→read', function (): void {
    $headers = ['_key', '_type', 'en - English (source)', 'it - Italian', '_vendor'];
    $rows = [
        ['_key' => 'auth.login', '_type' => 'text', 'en - English (source)' => 'Login', 'it - Italian' => 'Accedi', '_vendor' => ''],
        ['_key' => 'auth.logout', '_type' => 'text', 'en - English (source)' => 'Logout', 'it - Italian' => 'Esci', '_vendor' => ''],
    ];

    $path = tempnam(sys_get_temp_dir(), 'lingua_xlsx_');
    $writer = new XlsxWriter;
    $writer->write($path, $headers, $rows);

    $reader = new XlsxReader;
    $result = iterator_to_array($reader->read($path));
    @unlink($path);

    expect($result)->toHaveCount(3)
        ->and($result[0])->toBe($headers)
        ->and($result[1])->toBe(['auth.login', 'text', 'Login', 'Accedi', ''])
        ->and($result[2])->toBe(['auth.logout', 'text', 'Logout', 'Esci', '']);
})->skip(! SpreadsheetSupport::available(), 'OpenSpout not installed');

test('xlsx formula injection: value starting with equals is prefixed with quote', function (): void {
    $headers = ['_key', 'value'];
    $rows = [['_key' => 'k', 'value' => '=DANGER()']];

    $path = tempnam(sys_get_temp_dir(), 'lingua_xlsx_inject_');
    $writer = new XlsxWriter;
    $writer->write($path, $headers, $rows);

    $reader = new XlsxReader;
    $result = iterator_to_array($reader->read($path));
    @unlink($path);

    expect($result[1][1])->toBe("'=DANGER()");
})->skip(! SpreadsheetSupport::available(), 'OpenSpout not installed');

test('xlsx mimeType and extension are correct', function (): void {
    $writer = new XlsxWriter;
    expect($writer->mimeType())->toBe('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->and($writer->extension())->toBe('xlsx');
})->skip(! SpreadsheetSupport::available(), 'OpenSpout not installed');

// ── ODS round-trip (skipped when OpenSpout absent) ────────────────────────────

test('ods round-trip: headers and rows survive write→read', function (): void {
    $headers = ['_key', '_type', 'en - English (source)', 'it - Italian', '_vendor'];
    $rows = [
        ['_key' => 'messages.hello', '_type' => 'html', 'en - English (source)' => 'Hello', 'it - Italian' => 'Ciao', '_vendor' => ''],
    ];

    $path = tempnam(sys_get_temp_dir(), 'lingua_ods_');
    $writer = new OdsWriter;
    $writer->write($path, $headers, $rows);

    $reader = new OdsReader;
    $result = iterator_to_array($reader->read($path));
    @unlink($path);

    expect($result)->toHaveCount(2)
        ->and($result[0])->toBe($headers)
        ->and($result[1])->toBe(['messages.hello', 'html', 'Hello', 'Ciao', '']);
})->skip(! SpreadsheetSupport::available(), 'OpenSpout not installed');

test('ods mimeType and extension are correct', function (): void {
    $writer = new OdsWriter;
    expect($writer->mimeType())->toBe('application/vnd.oasis.opendocument.spreadsheet')
        ->and($writer->extension())->toBe('ods');
})->skip(! SpreadsheetSupport::available(), 'OpenSpout not installed');

// ── Format registry returns correct writer/reader instances when available ────

test('format registry writer returns XlsxWriter for xlsx when available', function (): void {
    $registry = new FormatRegistry;
    $writer = $registry->writer('xlsx');
    expect($writer)->toBeInstanceOf(XlsxWriter::class);
})->skip(! SpreadsheetSupport::available(), 'OpenSpout not installed');

test('format registry reader returns OdsReader for ods when available', function (): void {
    $registry = new FormatRegistry;
    $reader = $registry->reader('ods');
    expect($reader)->toBeInstanceOf(OdsReader::class);
})->skip(! SpreadsheetSupport::available(), 'OpenSpout not installed');
