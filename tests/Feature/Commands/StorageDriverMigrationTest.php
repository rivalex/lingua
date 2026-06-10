<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

// These tests verify that `lingua:storage {driver}` ensures the target driver's
// required migrations are published (and optionally run) BEFORE syncing data.

// ── Helpers ───────────────────────────────────────────────────────────────────

function sd_tempDir(): string
{
    $dir = sys_get_temp_dir().'/sd_test_'.uniqid();
    mkdir($dir, 0755, true);

    return $dir;
}

function sd_cleanDir(string $path): void
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

// ── §--no-migrate does not call migrate ──────────────────────────────────────

it('lingua:storage --no-migrate succeeds without calling artisan migrate', function (): void {
    // Switch from file → database with --no-migrate.
    // Regardless of whether migrations were already published, migrate must NOT run.
    config(['lingua.storage.driver' => 'file']);

    $this->artisan('lingua:storage', ['driver' => 'database', '--no-migrate' => true])
        ->assertSuccessful()
        ->doesntExpectOutputToContain('Running migrations...');
});

// ── §lingua:storage file does not require language_lines ─────────────────────

it('lingua:storage file driver switch does not require or publish language_lines migration', function (): void {
    config(['lingua.storage.driver' => 'database']);

    // language_lines should already be present from the test DB setup
    expect(Schema::hasTable('language_lines'))->toBeTrue();

    // Switching to file with an already-published DB should not fail
    $this->artisan('lingua:storage', ['driver' => 'file', '--force' => true])
        ->assertSuccessful();

    // language_lines table should still exist (we didn't drop it)
    expect(Schema::hasTable('language_lines'))->toBeTrue();

    // Restore
    config(['lingua.storage.driver' => 'database']);
});

// ── §already-same-driver short-circuits ──────────────────────────────────────

it('lingua:storage returns early when driver is already active', function (): void {
    config(['lingua.storage.driver' => 'database']);

    $this->artisan('lingua:storage', ['driver' => 'database'])
        ->assertSuccessful()
        ->expectsOutputToContain("Driver already set to 'database'");
});

// ── §invalid driver ───────────────────────────────────────────────────────────

it('lingua:storage fails on invalid driver argument', function (): void {
    $this->artisan('lingua:storage', ['driver' => 'redis'])
        ->assertFailed()
        ->expectsOutputToContain("Invalid driver 'redis'");
});
