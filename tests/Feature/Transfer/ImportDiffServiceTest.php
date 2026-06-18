<?php

declare(strict_types=1);

use Rivalex\Lingua\Contracts\BaseTranslationSource;
use Rivalex\Lingua\Database\DatabaseRepository;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\Transfer\Format\CsvWriter;
use Rivalex\Lingua\Transfer\Format\FormatRegistry;
use Rivalex\Lingua\Transfer\ImportDiffService;
use Rivalex\Lingua\Transfer\RowMapper;
use Rivalex\Lingua\Transfer\TransferSchema;

// Helper: write a CSV file with given headers and rows, return its path
function writeTempCsv(array $headers, array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'lingua_import_diff_');
    $writer = new CsvWriter;
    $writer->write($path, $headers, $rows);

    return $path;
}

// Helper: build the default bilingual CSV headers for en→it
function bilingualHeaders(): array
{
    return [
        TransferSchema::KEY,
        TransferSchema::TYPE,
        TransferSchema::sourceHeader('en'),
        TransferSchema::targetHeader('it'),
        TransferSchema::VENDOR,
    ];
}

beforeEach(function (): void {
    $this->repo = new DatabaseRepository(app(BaseTranslationSource::class));
    $this->diffService = new ImportDiffService($this->repo, new FormatRegistry, new RowMapper);
});

// ── Basic create/update/skip counts ──────────────────────────────────────────

test('diff counts create for keys not in repository', function (): void {
    // Empty repo — all rows should be creates
    $path = writeTempCsv(bilingualHeaders(), [
        [TransferSchema::KEY => 'auth.login', TransferSchema::TYPE => 'text', TransferSchema::sourceHeader('en') => 'Login', TransferSchema::targetHeader('it') => 'Accedi', TransferSchema::VENDOR => ''],
    ]);

    $diff = $this->diffService->diff($path, 'csv', 'it', false);
    @unlink($path);

    expect($diff->createCount)->toBe(1)
        ->and($diff->updateCount)->toBe(0)
        ->and($diff->skipCount)->toBe(0);
});

test('diff counts update for keys already in repository', function (): void {
    Translation::create(['group' => 'auth', 'key' => 'login', 'type' => LinguaType::text, 'text' => ['en' => 'Login'], 'is_vendor' => false, 'vendor' => null]);

    $path = writeTempCsv(bilingualHeaders(), [
        [TransferSchema::KEY => 'auth.login', TransferSchema::TYPE => 'text', TransferSchema::sourceHeader('en') => 'Login', TransferSchema::targetHeader('it') => 'Accedi', TransferSchema::VENDOR => ''],
    ]);

    $diff = $this->diffService->diff($path, 'csv', 'it', false);
    @unlink($path);

    expect($diff->updateCount)->toBe(1)
        ->and($diff->createCount)->toBe(0);
});

test('diff skips rows with empty target value', function (): void {
    $path = writeTempCsv(bilingualHeaders(), [
        [TransferSchema::KEY => 'auth.login', TransferSchema::TYPE => 'text', TransferSchema::sourceHeader('en') => 'Login', TransferSchema::targetHeader('it') => '', TransferSchema::VENDOR => ''],
    ]);

    $diff = $this->diffService->diff($path, 'csv', 'it', false);
    @unlink($path);

    expect($diff->skipCount)->toBe(1)
        ->and($diff->createCount)->toBe(0)
        ->and($diff->updateCount)->toBe(0);
});

test('diff skips rows with empty key', function (): void {
    $path = writeTempCsv(bilingualHeaders(), [
        [TransferSchema::KEY => '', TransferSchema::TYPE => 'text', TransferSchema::sourceHeader('en') => 'X', TransferSchema::targetHeader('it') => 'Y', TransferSchema::VENDOR => ''],
    ]);

    $diff = $this->diffService->diff($path, 'csv', 'it', false);
    @unlink($path);

    expect($diff->skipCount)->toBe(1)
        ->and($diff->createCount)->toBe(0);
});

// ── Vendor opt-in rules ───────────────────────────────────────────────────────

test('diff skips vendor rows when vendorUpdateEnabled=false', function (): void {
    Translation::create(['group' => 'pagination', 'key' => 'next', 'type' => LinguaType::text, 'text' => ['en' => 'Next'], 'is_vendor' => true, 'vendor' => 'spatie']);

    $path = writeTempCsv(bilingualHeaders(), [
        [TransferSchema::KEY => 'pagination.next', TransferSchema::TYPE => 'text', TransferSchema::sourceHeader('en') => 'Next', TransferSchema::targetHeader('it') => 'Successivo', TransferSchema::VENDOR => 'spatie'],
    ]);

    $diff = $this->diffService->diff($path, 'csv', 'it', false);
    @unlink($path);

    expect($diff->skipCount)->toBe(1)
        ->and($diff->updateCount)->toBe(0);

    $skip = $diff->skipped[0];
    expect($skip['reason'])->toContain('opt-in disabled');
});

test('diff counts update for existing vendor rows when vendorUpdateEnabled=true', function (): void {
    Translation::create(['group' => 'pagination', 'key' => 'next', 'type' => LinguaType::text, 'text' => ['en' => 'Next'], 'is_vendor' => true, 'vendor' => 'spatie']);

    $path = writeTempCsv(bilingualHeaders(), [
        [TransferSchema::KEY => 'pagination.next', TransferSchema::TYPE => 'text', TransferSchema::sourceHeader('en') => 'Next', TransferSchema::targetHeader('it') => 'Successivo', TransferSchema::VENDOR => 'spatie'],
    ]);

    $diff = $this->diffService->diff($path, 'csv', 'it', true);
    @unlink($path);

    expect($diff->updateCount)->toBe(1)
        ->and($diff->skipCount)->toBe(0);
    expect($diff->changes[0]['action'])->toContain('vendor');
});

test('diff skips non-existent vendor row even when vendorUpdateEnabled=true', function (): void {
    // Vendor row in CSV but NOT in repository — must be skipped (never created)
    $path = writeTempCsv(bilingualHeaders(), [
        [TransferSchema::KEY => 'pagination.prev', TransferSchema::TYPE => 'text', TransferSchema::sourceHeader('en') => 'Prev', TransferSchema::targetHeader('it') => 'Precedente', TransferSchema::VENDOR => 'spatie'],
    ]);

    $diff = $this->diffService->diff($path, 'csv', 'it', true);
    @unlink($path);

    expect($diff->updateCount)->toBe(0)
        ->and($diff->createCount)->toBe(0)
        ->and($diff->skipCount)->toBe(1);

    expect($diff->skipped[0]['reason'])->toContain('not found');
});

// ── Type precedence (plan §8) ─────────────────────────────────────────────────
// These are exercised by ImportCommitService, not ImportDiffService (diff does not apply types).
// The diff tests here verify counts; type tests live in ImportCommitServiceTest.

test('diff vendorUpdateEnabled flag is stored in diff', function (): void {
    $path = writeTempCsv(bilingualHeaders(), []);
    $diff = $this->diffService->diff($path, 'csv', 'it', true);
    @unlink($path);

    expect($diff->vendorUpdateEnabled)->toBeTrue();
});
