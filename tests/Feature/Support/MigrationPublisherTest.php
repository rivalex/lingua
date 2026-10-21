<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Rivalex\Lingua\Support\MigrationPublisher;

// ── Helpers ───────────────────────────────────────────────────────────────────

function mp_tempDir(): string
{
    $dir = sys_get_temp_dir().'/mp_test_'.uniqid();
    mkdir($dir, 0755, true);

    return $dir;
}

function mp_cleanDir(string $path): void
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

function makePublisher(): MigrationPublisher
{
    return new MigrationPublisher(new Filesystem);
}

// ── §publishFor('file') ───────────────────────────────────────────────────────

it('publishFor file copies only languages and lingua_settings', function (): void {
    $dir = mp_tempDir();

    $published = makePublisher()->publishFor('file', $dir);

    expect($published)->toContain('create_languages_table')
        ->toContain('create_lingua_settings_table')
        ->not->toContain('create_language_lines_table');

    // Files must exist in the target dir
    $files = (new Filesystem)->files($dir);
    $names = array_map(fn ($f) => $f->getFilename(), $files);
    $hasLanguages = array_filter($names, fn ($n) => str_ends_with($n, '_create_languages_table.php'));
    $hasSettings = array_filter($names, fn ($n) => str_ends_with($n, '_create_lingua_settings_table.php'));
    $hasLines = array_filter($names, fn ($n) => str_ends_with($n, '_create_language_lines_table.php'));

    expect(count($hasLanguages))->toBe(1)
        ->and(count($hasSettings))->toBe(1)
        ->and(count($hasLines))->toBe(0);

    mp_cleanDir($dir);
});

// ── §publishFor('database') ───────────────────────────────────────────────────

it('publishFor database copies all three migration files', function (): void {
    $dir = mp_tempDir();

    $published = makePublisher()->publishFor('database', $dir);

    expect($published)->toContain('create_languages_table')
        ->toContain('create_lingua_settings_table')
        ->toContain('create_language_lines_table');

    $files = (new Filesystem)->files($dir);
    expect(count($files))->toBe(3);

    mp_cleanDir($dir);
});

// ── §idempotency ──────────────────────────────────────────────────────────────

it('publishFor is idempotent — second call returns empty array', function (): void {
    $dir = mp_tempDir();
    $publisher = makePublisher();

    $first = $publisher->publishFor('database', $dir);
    $second = $publisher->publishFor('database', $dir);

    expect(count($first))->toBe(3)
        ->and($second)->toBe([]);

    mp_cleanDir($dir);
});

it('publishFor skips basenames already present regardless of timestamp prefix', function (): void {
    $dir = mp_tempDir();
    $publisher = makePublisher();

    // Manually place a file that simulates a previously published migration
    touch($dir.'/2020_01_01_000000_create_languages_table.php');

    $published = $publisher->publishFor('database', $dir);

    expect($published)->not->toContain('create_languages_table')
        ->and($published)->toContain('create_lingua_settings_table')
        ->and($published)->toContain('create_language_lines_table');

    mp_cleanDir($dir);
});

// ── §isPublishedFor ───────────────────────────────────────────────────────────

it('isPublishedFor returns false when directory is empty', function (): void {
    $dir = mp_tempDir();

    expect(makePublisher()->isPublishedFor('database', $dir))->toBeFalse();

    mp_cleanDir($dir);
});

it('isPublishedFor returns true after publishing', function (): void {
    $dir = mp_tempDir();
    $publisher = makePublisher();

    $publisher->publishFor('database', $dir);

    expect($publisher->isPublishedFor('database', $dir))->toBeTrue();

    mp_cleanDir($dir);
});

it('isPublishedFor file returns true after file publish', function (): void {
    $dir = mp_tempDir();
    $publisher = makePublisher();

    $publisher->publishFor('file', $dir);

    expect($publisher->isPublishedFor('file', $dir))->toBeTrue();

    mp_cleanDir($dir);
});

it('isPublishedFor database returns false when only file migrations published', function (): void {
    $dir = mp_tempDir();
    $publisher = makePublisher();

    $publisher->publishFor('file', $dir);

    // database needs language_lines which was not published
    expect($publisher->isPublishedFor('database', $dir))->toBeFalse();

    mp_cleanDir($dir);
});
