<?php

declare(strict_types=1);

use Rivalex\Lingua\Contracts\BaseTranslationSource;
use Rivalex\Lingua\Database\DatabaseRepository;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\Transfer\Format\CsvWriter;
use Rivalex\Lingua\Transfer\Format\FormatRegistry;
use Rivalex\Lingua\Transfer\ImportCommitService;
use Rivalex\Lingua\Transfer\ImportDiffService;
use Rivalex\Lingua\Transfer\RowMapper;
use Rivalex\Lingua\Transfer\TransferSchema;

// Helper: write a CSV temp file and return its path
function writeTempCsvForCommit(array $headers, array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'lingua_commit_');
    $writer = new CsvWriter;
    $writer->write($path, $headers, $rows);

    return $path;
}

function commitBilingualHeaders(): array
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
    $registry = new FormatRegistry;
    $mapper = new RowMapper;
    $this->diffService = new ImportDiffService($this->repo, $registry, $mapper);
    $this->commitService = new ImportCommitService($this->repo, $registry, $mapper, $this->diffService);
});

// ── Preview counts == committed counts ────────────────────────────────────────

test('preview counts equal commit counts (create)', function (): void {
    $path = writeTempCsvForCommit(commitBilingualHeaders(), [
        [TransferSchema::KEY => 'auth.login', TransferSchema::TYPE => 'text', TransferSchema::sourceHeader('en') => 'Login', TransferSchema::targetHeader('it') => 'Accedi', TransferSchema::VENDOR => ''],
        [TransferSchema::KEY => 'auth.logout', TransferSchema::TYPE => 'text', TransferSchema::sourceHeader('en') => 'Logout', TransferSchema::targetHeader('it') => 'Esci', TransferSchema::VENDOR => ''],
    ]);

    $preview = $this->diffService->diff($path, 'csv', 'it', false);
    $committed = $this->commitService->commit($path, 'csv', 'it', false);
    @unlink($path);

    expect($committed->createCount)->toBe($preview->createCount)
        ->and($committed->updateCount)->toBe($preview->updateCount)
        ->and($committed->skipCount)->toBe($preview->skipCount);
});

test('preview counts equal commit counts (update)', function (): void {
    Translation::create(['group' => 'auth', 'key' => 'login', 'type' => LinguaType::text, 'text' => ['en' => 'Login'], 'is_vendor' => false, 'vendor' => null]);

    $path = writeTempCsvForCommit(commitBilingualHeaders(), [
        [TransferSchema::KEY => 'auth.login', TransferSchema::TYPE => 'text', TransferSchema::sourceHeader('en') => 'Login', TransferSchema::targetHeader('it') => 'Accedi', TransferSchema::VENDOR => ''],
    ]);

    $preview = $this->diffService->diff($path, 'csv', 'it', false);
    $committed = $this->commitService->commit($path, 'csv', 'it', false);
    @unlink($path);

    expect($committed->updateCount)->toBe($preview->updateCount)
        ->and($committed->createCount)->toBe($preview->createCount);
});

// ── Commit actually writes to the repository ──────────────────────────────────

test('commit writes target locale value for new key', function (): void {
    $path = writeTempCsvForCommit(commitBilingualHeaders(), [
        [TransferSchema::KEY => 'messages.hello', TransferSchema::TYPE => 'text', TransferSchema::sourceHeader('en') => 'Hello', TransferSchema::targetHeader('it') => 'Ciao', TransferSchema::VENDOR => ''],
    ]);

    $this->commitService->commit($path, 'csv', 'it', false);
    @unlink($path);

    $line = $this->repo->find('messages', 'hello', false, null);
    expect($line)->not->toBeNull()
        ->and($line->value('it'))->toBe('Ciao');
});

test('commit updates target locale value for existing key', function (): void {
    Translation::create(['group' => 'auth', 'key' => 'login', 'type' => LinguaType::text, 'text' => ['en' => 'Login', 'it' => 'Old'], 'is_vendor' => false, 'vendor' => null]);

    $path = writeTempCsvForCommit(commitBilingualHeaders(), [
        [TransferSchema::KEY => 'auth.login', TransferSchema::TYPE => 'text', TransferSchema::sourceHeader('en') => 'Login', TransferSchema::targetHeader('it') => 'Accedi', TransferSchema::VENDOR => ''],
    ]);

    $this->commitService->commit($path, 'csv', 'it', false);
    @unlink($path);

    $line = $this->repo->find('auth', 'login', false, null);
    expect($line->value('it'))->toBe('Accedi');
});

test('commit does not write other locales from source column', function (): void {
    Translation::create(['group' => 'auth', 'key' => 'login', 'type' => LinguaType::text, 'text' => ['en' => 'Login', 'fr' => 'Connexion'], 'is_vendor' => false, 'vendor' => null]);

    $path = writeTempCsvForCommit(commitBilingualHeaders(), [
        // _source column is "en - English (source)" — import should NOT touch 'en' value
        [TransferSchema::KEY => 'auth.login', TransferSchema::TYPE => 'text', TransferSchema::sourceHeader('en') => 'NEW SOURCE VALUE', TransferSchema::targetHeader('it') => 'Accedi', TransferSchema::VENDOR => ''],
    ]);

    $this->commitService->commit($path, 'csv', 'it', false);
    @unlink($path);

    $line = $this->repo->find('auth', 'login', false, null);
    // 'en' must NOT have been overwritten by the source column content
    expect($line->value('en'))->toBe('Login')
        // 'fr' must be untouched
        ->and($line->value('fr'))->toBe('Connexion')
        // 'it' was written
        ->and($line->value('it'))->toBe('Accedi');
});

// ── Type precedence (plan §8) ─────────────────────────────────────────────────

test('type precedence (a): existing key, no _type column — stored type unchanged', function (): void {
    Translation::create(['group' => 'auth', 'key' => 'login', 'type' => LinguaType::html, 'text' => ['en' => 'Login'], 'is_vendor' => false, 'vendor' => null]);

    // Headers without _type column
    $headers = [TransferSchema::KEY, TransferSchema::targetHeader('it'), TransferSchema::VENDOR];
    $path = writeTempCsvForCommit($headers, [
        [TransferSchema::KEY => 'auth.login', TransferSchema::targetHeader('it') => 'Accedi', TransferSchema::VENDOR => ''],
    ]);

    $this->commitService->commit($path, 'csv', 'it', false);
    @unlink($path);

    $line = $this->repo->find('auth', 'login', false, null);
    expect($line->type)->toBe(LinguaType::html); // unchanged
});

test('type precedence (b): existing key, _type=html — type becomes html', function (): void {
    Translation::create(['group' => 'auth', 'key' => 'login', 'type' => LinguaType::text, 'text' => ['en' => 'Login'], 'is_vendor' => false, 'vendor' => null]);

    $path = writeTempCsvForCommit(commitBilingualHeaders(), [
        [TransferSchema::KEY => 'auth.login', TransferSchema::TYPE => 'html', TransferSchema::sourceHeader('en') => 'Login', TransferSchema::targetHeader('it') => 'Accedi', TransferSchema::VENDOR => ''],
    ]);

    $this->commitService->commit($path, 'csv', 'it', false);
    @unlink($path);

    $line = $this->repo->find('auth', 'login', false, null);
    expect($line->type)->toBe(LinguaType::html);
});

test('type precedence (c): existing key, _type=garbage — stored type kept', function (): void {
    Translation::create(['group' => 'auth', 'key' => 'login', 'type' => LinguaType::markdown, 'text' => ['en' => 'Login'], 'is_vendor' => false, 'vendor' => null]);

    $path = writeTempCsvForCommit(commitBilingualHeaders(), [
        [TransferSchema::KEY => 'auth.login', TransferSchema::TYPE => 'garbage_invalid', TransferSchema::sourceHeader('en') => 'Login', TransferSchema::targetHeader('it') => 'Accedi', TransferSchema::VENDOR => ''],
    ]);

    $this->commitService->commit($path, 'csv', 'it', false);
    @unlink($path);

    $line = $this->repo->find('auth', 'login', false, null);
    expect($line->type)->toBe(LinguaType::markdown); // unchanged
});

test('type precedence (d): new key, no _type column — defaults to text', function (): void {
    $headers = [TransferSchema::KEY, TransferSchema::targetHeader('it'), TransferSchema::VENDOR];
    $path = writeTempCsvForCommit($headers, [
        [TransferSchema::KEY => 'newgroup.newkey', TransferSchema::targetHeader('it') => 'Nuovo', TransferSchema::VENDOR => ''],
    ]);

    $this->commitService->commit($path, 'csv', 'it', false);
    @unlink($path);

    $line = $this->repo->find('newgroup', 'newkey', false, null);
    expect($line)->not->toBeNull()
        ->and($line->type)->toBe(LinguaType::text);
});

test('type precedence (e): new key, _type=markdown — type is markdown', function (): void {
    $path = writeTempCsvForCommit(commitBilingualHeaders(), [
        [TransferSchema::KEY => 'docs.readme', TransferSchema::TYPE => 'markdown', TransferSchema::sourceHeader('en') => 'Readme', TransferSchema::targetHeader('it') => 'Leggimi', TransferSchema::VENDOR => ''],
    ]);

    $this->commitService->commit($path, 'csv', 'it', false);
    @unlink($path);

    $line = $this->repo->find('docs', 'readme', false, null);
    expect($line)->not->toBeNull()
        ->and($line->type)->toBe(LinguaType::markdown);
});

// ── Vendor rules ──────────────────────────────────────────────────────────────

test('commit updates existing vendor row when vendorUpdateEnabled=true', function (): void {
    Translation::create(['group' => 'pagination', 'key' => 'next', 'type' => LinguaType::text, 'text' => ['en' => 'Next'], 'is_vendor' => true, 'vendor' => 'spatie']);

    $path = writeTempCsvForCommit(commitBilingualHeaders(), [
        [TransferSchema::KEY => 'pagination.next', TransferSchema::TYPE => 'text', TransferSchema::sourceHeader('en') => 'Next', TransferSchema::targetHeader('it') => 'Successivo', TransferSchema::VENDOR => 'spatie'],
    ]);

    $this->commitService->commit($path, 'csv', 'it', true);
    @unlink($path);

    $line = $this->repo->find('pagination', 'next', true, 'spatie');
    expect($line->value('it'))->toBe('Successivo');
});

test('commit never creates vendor rows (non-existent vendor key is skipped)', function (): void {
    $path = writeTempCsvForCommit(commitBilingualHeaders(), [
        [TransferSchema::KEY => 'pagination.prev', TransferSchema::TYPE => 'text', TransferSchema::sourceHeader('en') => 'Prev', TransferSchema::targetHeader('it') => 'Precedente', TransferSchema::VENDOR => 'spatie'],
    ]);

    $this->commitService->commit($path, 'csv', 'it', true);
    @unlink($path);

    // The vendor row must NOT have been created
    $line = $this->repo->find('pagination', 'prev', true, 'spatie');
    expect($line)->toBeNull();

    // Also confirm no app row was created with this key
    $appLine = $this->repo->find('pagination', 'prev', false, null);
    expect($appLine)->toBeNull();
});

test('commit skips vendor rows when vendorUpdateEnabled=false even if they exist', function (): void {
    Translation::create(['group' => 'pagination', 'key' => 'next', 'type' => LinguaType::text, 'text' => ['en' => 'Next', 'it' => 'Original'], 'is_vendor' => true, 'vendor' => 'spatie']);

    $path = writeTempCsvForCommit(commitBilingualHeaders(), [
        [TransferSchema::KEY => 'pagination.next', TransferSchema::TYPE => 'text', TransferSchema::sourceHeader('en') => 'Next', TransferSchema::targetHeader('it') => 'ShouldNotChange', TransferSchema::VENDOR => 'spatie'],
    ]);

    $this->commitService->commit($path, 'csv', 'it', false);
    @unlink($path);

    $line = $this->repo->find('pagination', 'next', true, 'spatie');
    expect($line->value('it'))->toBe('Original'); // unchanged
});

// ── Single-locale: only target locale is written ──────────────────────────────

test('commit writes only the declared target locale, leaving other locales untouched', function (): void {
    Translation::create(['group' => 'auth', 'key' => 'login', 'type' => LinguaType::text, 'text' => ['en' => 'Login', 'fr' => 'Connexion'], 'is_vendor' => false, 'vendor' => null]);

    $path = writeTempCsvForCommit(commitBilingualHeaders(), [
        [TransferSchema::KEY => 'auth.login', TransferSchema::TYPE => 'text', TransferSchema::sourceHeader('en') => 'Login', TransferSchema::targetHeader('it') => 'Accedi', TransferSchema::VENDOR => ''],
    ]);

    $this->commitService->commit($path, 'csv', 'it', false);
    @unlink($path);

    $line = $this->repo->find('auth', 'login', false, null);
    expect($line->value('it'))->toBe('Accedi')
        ->and($line->value('fr'))->toBe('Connexion') // untouched
        ->and($line->value('en'))->toBe('Login');    // untouched
});
