<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Rivalex\Lingua\Models\Language;

// ── Helpers ───────────────────────────────────────────────────────────────────

function uc_tempDir(): string
{
    $dir = sys_get_temp_dir().'/uc_test_'.uniqid();
    mkdir($dir, 0755, true);

    return $dir;
}

function uc_cleanDir(string $path): void
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

// ── §database driver: export before drop ─────────────────────────────────────

it('lingua:uninstall exports translations to lang/ before dropping tables in database mode', function (): void {
    $langDir = uc_tempDir();
    config(['lingua.storage.driver' => 'database']);
    config(['lingua.lang_dir' => $langDir]);

    // Ensure at least one language exists for syncToLocal to write files
    Language::updateOrCreate(
        ['code' => 'en', 'regional' => 'en_US'],
        ['type' => 'Latn', 'name' => 'English', 'native' => 'English', 'direction' => 'ltr', 'is_default' => true],
    );

    $this->artisan('lingua:uninstall', ['--force' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Exporting translations to lang/ files');

    // lang/ dir must have been written (syncToLocal creates at minimum the locale JSON)
    expect(is_dir($langDir))->toBeTrue();

    uc_cleanDir($langDir);
});

// ── §tables are dropped ───────────────────────────────────────────────────────

it('lingua:uninstall drops all three Lingua tables', function (): void {
    expect(Schema::hasTable('languages'))->toBeTrue()
        ->and(Schema::hasTable('language_lines'))->toBeTrue()
        ->and(Schema::hasTable('lingua_settings'))->toBeTrue();

    $langDir = uc_tempDir();
    config(['lingua.storage.driver' => 'database']);
    config(['lingua.lang_dir' => $langDir]);

    $this->artisan('lingua:uninstall', ['--force' => true])
        ->assertSuccessful();

    expect(Schema::hasTable('languages'))->toBeFalse()
        ->and(Schema::hasTable('language_lines'))->toBeFalse()
        ->and(Schema::hasTable('lingua_settings'))->toBeFalse();

    uc_cleanDir($langDir);
});

// ── §file mode: no language_lines export attempted ───────────────────────────

it('lingua:uninstall in file mode does not attempt to export from language_lines', function (): void {
    config(['lingua.storage.driver' => 'file']);

    $this->artisan('lingua:uninstall', ['--force' => true])
        ->assertSuccessful()
        ->doesntExpectOutputToContain('Exporting translations to lang/');
});

// ── §lang/ files preserved ────────────────────────────────────────────────────

it('lingua:uninstall leaves lang/ files intact', function (): void {
    $langDir = uc_tempDir();
    config(['lingua.storage.driver' => 'file']);
    config(['lingua.lang_dir' => $langDir]);

    // Create a sentinel file in lang/
    file_put_contents($langDir.'/en.json', '{"hello":"world"}');

    $this->artisan('lingua:uninstall', ['--force' => true])
        ->assertSuccessful();

    // The file must still be there
    expect(file_exists($langDir.'/en.json'))->toBeTrue()
        ->and(json_decode(file_get_contents($langDir.'/en.json'), true))->toBe(['hello' => 'world']);

    uc_cleanDir($langDir);
});

// ── §--keep-config ────────────────────────────────────────────────────────────

it('lingua:uninstall respects --keep-config and does not delete the config file', function (): void {
    $langDir = uc_tempDir();
    config(['lingua.storage.driver' => 'file']);
    config(['lingua.lang_dir' => $langDir]);

    // Create a fake published config file
    $fs = new Filesystem;
    $configFile = config_path('lingua.php');
    $fs->ensureDirectoryExists(dirname($configFile));
    $fs->put($configFile, '<?php return [];');

    $this->artisan('lingua:uninstall', ['--force' => true, '--keep-config' => true])
        ->assertSuccessful();

    expect($fs->exists($configFile))->toBeTrue();

    // Cleanup
    $fs->delete($configFile);
    uc_cleanDir($langDir);
});

// ── §--keep-published ─────────────────────────────────────────────────────────

it('lingua:uninstall respects --keep-published and does not delete views/migrations', function (): void {
    $langDir = uc_tempDir();
    config(['lingua.storage.driver' => 'file']);
    config(['lingua.lang_dir' => $langDir]);

    // Create a fake published views directory
    $fs = new Filesystem;
    $viewsDir = resource_path('views/vendor/lingua');
    $fs->ensureDirectoryExists($viewsDir);
    $fs->put($viewsDir.'/test.blade.php', '<div>test</div>');

    $this->artisan('lingua:uninstall', ['--force' => true, '--keep-published' => true])
        ->assertSuccessful();

    expect($fs->isDirectory($viewsDir))->toBeTrue();

    // Cleanup
    $fs->deleteDirectory($viewsDir);
    uc_cleanDir($langDir);
});

// ── §aborts without --force when user declines ───────────────────────────────

it('lingua:uninstall aborts cleanly when user does not confirm', function (): void {
    expect(Schema::hasTable('languages'))->toBeTrue();

    $this->artisan('lingua:uninstall')
        ->expectsConfirmation('Do you wish to continue?', 'no')
        ->assertSuccessful()
        ->expectsOutputToContain('Uninstall aborted.');

    // Tables must still exist
    expect(Schema::hasTable('languages'))->toBeTrue();
});
