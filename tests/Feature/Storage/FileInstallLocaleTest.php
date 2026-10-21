<?php

declare(strict_types=1);

use Livewire\Livewire;
use Rivalex\Lingua\Facades\Lingua;
use Rivalex\Lingua\Livewire\Languages;
use Rivalex\Lingua\Locales\BundledTranslationSource;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Storage\FileRepository;
use Rivalex\Lingua\Support\AtomicFileWriter;
use Rivalex\Lingua\Support\TranslationFileReader;

// ── Helpers ───────────────────────────────────────────────────────────────────

function fil_tempDir(): string
{
    $dir = sys_get_temp_dir().'/fil_test_'.uniqid();
    mkdir($dir, 0755, true);

    return $dir;
}

function fil_cleanDir(string $path): void
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

function makeInstallRepo(string $langDir, string $bundledPath): FileRepository
{
    return new FileRepository(
        writer: new AtomicFileWriter,
        reader: new TranslationFileReader,
        langPath: $langDir,
        bundled: new BundledTranslationSource(basePath: $bundledPath),
    );
}

// ── §installLocale: unknown locale (no bundled data) ─────────────────────────

it('installLocale unknown locale creates lang/ dir and empty {locale}.json', function (): void {
    $dir = fil_tempDir();

    $repo = makeInstallRepo($dir, '/dev/null/no-bundled');
    $repo->installLocale('zz');

    expect(is_dir($dir))->toBeTrue()
        ->and(file_exists($dir.'/zz.json'))->toBeTrue();

    $decoded = json_decode(file_get_contents($dir.'/zz.json'), true);
    expect($decoded)->toBe([]);

    fil_cleanDir($dir);
});

// ── §installLocale: locale with bundled data ──────────────────────────────────

it('installLocale known locale writes bundled values into lang files', function (): void {
    $dir = fil_tempDir();
    $bundledPath = __DIR__.'/../../../resources/translations';

    $repo = makeInstallRepo($dir, $bundledPath);
    $repo->installLocale('en');

    // lang/ dir exists
    expect(is_dir($dir))->toBeTrue();
    // at least one PHP group file was created in lang/en/
    $phpFiles = glob($dir.'/en/*.php') ?: [];
    expect(count($phpFiles))->toBeGreaterThan(0);

    fil_cleanDir($dir);
});

// ── §installLocale: non-default locale mirrors default keys ───────────────────

it('installLocale non-default locale mirrors default-locale key structure with empty values', function (): void {
    $dir = fil_tempDir();
    $bundledPath = __DIR__.'/../../../resources/translations';

    // First install the default ('en') so there are files to mirror
    $repo = makeInstallRepo($dir, $bundledPath);
    $repo->installLocale('en');

    // Now install 'it' — should mirror en keys
    $repo->installLocale('it');

    // lang/it.json must exist (guaranteed)
    expect(file_exists($dir.'/it.json'))->toBeTrue();

    // At least one PHP file exists (mirrored from en)
    $phpFiles = glob($dir.'/it/*.php') ?: [];
    expect(count($phpFiles))->toBeGreaterThan(0);

    // Pick one PHP file and verify it's a valid PHP array
    $loaded = include $phpFiles[0];
    expect(is_array($loaded))->toBeTrue();

    fil_cleanDir($dir);
});

// ── §installDefaultLanguage via Lingua facade ────────────────────────────────

it('installDefaultLanguage creates default Language record in file mode', function (): void {
    config(['lingua.storage.driver' => 'file']);
    config(['lingua.lang_dir' => fil_tempDir()]);

    Language::query()->delete();

    Lingua::installDefaultLanguage();

    $default = Language::where('is_default', true)->first();
    expect($default)->not->toBeNull()
        ->and($default->is_default)->toBeTrue();

    fil_cleanDir(config('lingua.lang_dir'));
});

it('installDefaultLanguage creates lang/ dir in file mode', function (): void {
    $dir = fil_tempDir();
    fil_cleanDir($dir); // remove so it doesn't exist yet

    config(['lingua.storage.driver' => 'file']);
    config(['lingua.lang_dir' => $dir]);

    Language::query()->delete();

    Lingua::installDefaultLanguage();

    expect(is_dir($dir))->toBeTrue();

    fil_cleanDir($dir);
});

// ── §Languages component: fileMode flag and sync button visibility ────────────

it('Languages component sets fileMode=true in file mode', function (): void {
    config(['lingua.storage.driver' => 'file']);

    Livewire::test(Languages::class)
        ->assertSet('fileMode', true);
});

it('Languages component sets fileMode=false in database mode', function (): void {
    config(['lingua.storage.driver' => 'database']);

    Livewire::test(Languages::class)
        ->assertSet('fileMode', false);
});

it('Languages blade hides sync buttons in file mode', function (): void {
    config(['lingua.storage.driver' => 'file']);

    Livewire::test(Languages::class)
        ->assertDontSeeHtml('wire:click="syncToLocal"')
        ->assertDontSeeHtml('wire:click="syncToDatabase"')
        ->assertDontSeeHtml('wire:click="updateLanguages"');
});

it('Languages blade shows sync buttons in database mode', function (): void {
    config(['lingua.storage.driver' => 'database']);

    Livewire::test(Languages::class)
        ->assertSeeHtml('wire:click="syncToLocal"')
        ->assertSeeHtml('wire:click="syncToDatabase"')
        ->assertSeeHtml('wire:click="updateLanguages"');
});

// ── §Server-side guards: sync calls are no-ops in file mode ──────────────────

it('syncToLocal is a no-op in file mode (no event dispatched)', function (): void {
    config(['lingua.storage.driver' => 'file']);

    Livewire::test(Languages::class)
        ->call('syncToLocal')
        ->assertNotDispatched('synced_local')
        ->assertNotDispatched('synced_local_fail');
});

it('syncToDatabase is a no-op in file mode (no event dispatched)', function (): void {
    config(['lingua.storage.driver' => 'file']);

    Livewire::test(Languages::class)
        ->call('syncToDatabase')
        ->assertNotDispatched('synced_database')
        ->assertNotDispatched('synced_database_fail');
});

it('updateLanguages is a no-op in file mode (no event dispatched)', function (): void {
    config(['lingua.storage.driver' => 'file']);

    Livewire::test(Languages::class)
        ->call('updateLanguages')
        ->assertNotDispatched('lang_updated')
        ->assertNotDispatched('lang_updated_fail');
});

// ── §Lazy bootstrap: Languages mount with no languages in file mode ───────────

it('Languages mount bootstraps default language in file mode when none exist', function (): void {
    $dir = fil_tempDir();
    fil_cleanDir($dir); // ensure no dir

    config(['lingua.storage.driver' => 'file']);
    config(['lingua.lang_dir' => $dir]);

    Language::query()->delete();

    Livewire::test(Languages::class);

    expect(Language::where('is_default', true)->exists())->toBeTrue();

    fil_cleanDir($dir);
});
