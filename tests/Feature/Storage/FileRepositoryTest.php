<?php

declare(strict_types=1);

use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Locales\BundledTranslationSource;
use Rivalex\Lingua\Storage\FileRepository;
use Rivalex\Lingua\Support\AtomicFileWriter;
use Rivalex\Lingua\Support\TranslationFileReader;

function fr_tempDir(): string
{
    $dir = sys_get_temp_dir().'/fr_test_'.uniqid();
    mkdir($dir, 0755, true);

    return $dir;
}

function fr_cleanDir(string $path): void
{
    if (! is_dir($path)) {
        return;
    }
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($files as $f) {
        $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
    }
    rmdir($path);
}

/**
 * Build a FileRepository pointed at $langDir.
 * Pass $bundledPath to use real bundled data; defaults to a non-existent path
 * (BundledTranslationSource returns [] when the directory is absent).
 */
function makeFileRepo(string $langDir, string $bundledPath = '/dev/null/no-bundled'): FileRepository
{
    return new FileRepository(
        writer: new AtomicFileWriter,
        reader: new TranslationFileReader,
        langPath: $langDir,
        bundled: new BundledTranslationSource(basePath: $bundledPath),
    );
}

// ── §8.2 write→read round-trip ────────────────────────────────────────────────

test('create core group writes PHP file and find re-reads', function (): void {
    $dir = fr_tempDir();
    $repo = makeFileRepo($dir);

    $line = $repo->create('messages', 'hi', LinguaType::text, 'en', 'Hello');

    expect($line->group)->toBe('messages')
        ->and($line->key)->toBe('hi')
        ->and($line->value('en'))->toBe('Hello')
        ->and(file_exists($dir.'/en/messages.php'))->toBeTrue();

    $found = $repo->find('messages', 'hi', false, null);
    expect($found)->not->toBeNull()
        ->and($found->value('en'))->toBe('Hello');

    fr_cleanDir($dir);
});

test('create single group writes JSON file', function (): void {
    $dir = fr_tempDir();
    $repo = makeFileRepo($dir);

    $repo->create('single', 'welcome', LinguaType::text, 'en', 'Welcome');

    expect(file_exists($dir.'/en.json'))->toBeTrue();
    $decoded = json_decode(file_get_contents($dir.'/en.json'), true);
    expect($decoded['welcome'])->toBe('Welcome');

    fr_cleanDir($dir);
});

test('setValue updates value in existing PHP file', function (): void {
    $dir = fr_tempDir();
    $repo = makeFileRepo($dir);

    $line = $repo->create('messages', 'hi', LinguaType::text, 'en', 'Hello');
    $updated = $repo->setValue($line, 'en', 'Hi there');

    expect($updated->value('en'))->toBe('Hi there');

    $found = $repo->find('messages', 'hi', false, null);
    expect($found->value('en'))->toBe('Hi there');

    fr_cleanDir($dir);
});

test('forgetLocale removes key from locale file', function (): void {
    $dir = fr_tempDir();
    $repo = makeFileRepo($dir);

    $line = $repo->create('messages', 'hi', LinguaType::text, 'en', 'Hello');
    $repo->setValue($line, 'it', 'Ciao');

    $line2 = $repo->find('messages', 'hi', false, null);
    $repo->forgetLocale($line2, 'it');

    $found = $repo->find('messages', 'hi', false, null);
    expect($found->value('it'))->toBe('');

    fr_cleanDir($dir);
});

test('deleteKey removes key from all locale files', function (): void {
    $dir = fr_tempDir();
    $repo = makeFileRepo($dir);

    $line = $repo->create('messages', 'hi', LinguaType::text, 'en', 'Hello');
    $repo->setValue($line, 'it', 'Ciao');

    $line2 = $repo->find('messages', 'hi', false, null);
    $repo->deleteKey($line2);

    expect($repo->find('messages', 'hi', false, null))->toBeNull();

    fr_cleanDir($dir);
});

// ── §8.3 path safety ─────────────────────────────────────────────────────────

test('create with traversal locale throws', function (): void {
    $dir = fr_tempDir();
    $repo = makeFileRepo($dir);

    expect(fn () => $repo->create('messages', 'key', LinguaType::text, '../etc', 'val'))
        ->toThrow(InvalidArgumentException::class);

    fr_cleanDir($dir);
});

test('create with traversal group throws', function (): void {
    $dir = fr_tempDir();
    $repo = makeFileRepo($dir);

    expect(fn () => $repo->create('../bad', 'key', LinguaType::text, 'en', 'val'))
        ->toThrow(InvalidArgumentException::class);

    fr_cleanDir($dir);
});

test('no files written outside lang_dir on path attack', function (): void {
    $dir = fr_tempDir();
    $repo = makeFileRepo($dir);

    try {
        $repo->create('messages', 'key', LinguaType::text, '../../../etc', 'val');
    } catch (InvalidArgumentException) {
    }

    expect(glob(sys_get_temp_dir().'/etc*') ?: [])->toBeEmpty();

    fr_cleanDir($dir);
});

// ── §8.10 counts PHP ─────────────────────────────────────────────────────────

test('counts returns correct total and byLocale', function (): void {
    $dir = fr_tempDir();
    $repo = makeFileRepo($dir);

    $l = $repo->create('messages', 'hi', LinguaType::text, 'en', 'Hello');
    $repo->setValue($l, 'it', 'Ciao');
    $repo->create('messages', 'bye', LinguaType::text, 'en', 'Bye');

    $counts = $repo->counts();

    expect($counts['total'])->toBe(2)
        ->and($counts['byLocale']['en'])->toBe(2)
        ->and($counts['byLocale']['it'])->toBe(1);

    fr_cleanDir($dir);
});

test('localeStats reflects file data', function (): void {
    $dir = fr_tempDir();
    $repo = makeFileRepo($dir);

    $l = $repo->create('messages', 'hi', LinguaType::text, 'en', 'Hello');
    $repo->create('messages', 'bye', LinguaType::text, 'en', 'Bye');
    $repo->setValue($l, 'it', 'Ciao');

    $stats = $repo->localeStats('it');

    expect($stats['total'])->toBe(2)
        ->and($stats['translated'])->toBe(1)
        ->and($stats['missing'])->toBe(1);

    fr_cleanDir($dir);
});

// ── updateMeta file-mode (§8.13) ─────────────────────────────────────────────

test('updateMeta same group and key returns line unchanged', function (): void {
    $dir = fr_tempDir();
    $repo = makeFileRepo($dir);

    $line = $repo->create('messages', 'hi', LinguaType::text, 'en', 'Hello');
    $result = $repo->updateMeta($line, 'messages', 'hi', LinguaType::text);

    expect($result->identity())->toBe($line->identity());

    fr_cleanDir($dir);
});

test('updateMeta different group throws RuntimeException', function (): void {
    $dir = fr_tempDir();
    $repo = makeFileRepo($dir);

    $line = $repo->create('messages', 'hi', LinguaType::text, 'en', 'Hello');

    expect(fn () => $repo->updateMeta($line, 'other_group', 'hi', LinguaType::text))
        ->toThrow(RuntimeException::class);

    fr_cleanDir($dir);
});
