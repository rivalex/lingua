<?php

declare(strict_types=1);

use Rivalex\Lingua\Facades\Lingua;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

// ─── Per-test isolated lang + bundled directories ───────────────────────────

beforeEach(function () {
    $suffix = str_replace('.', '_', uniqid('', true));
    $this->syncDir = sys_get_temp_dir().'/lingua_sync_'.$suffix;
    $this->bundleDir = sys_get_temp_dir().'/lingua_bundle_'.$suffix;
    mkdir($this->syncDir, 0777, true);
    mkdir($this->bundleDir, 0777, true);
    config(['lingua.lang_dir' => $this->syncDir]);
    config(['lingua.base_translations_path' => $this->bundleDir]);
    Translation::query()->delete();
});

afterEach(function () {
    foreach ([$this->syncDir, $this->bundleDir] as $dir) {
        if (! is_dir($dir)) {
            continue;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getRealPath()) : unlink($item->getRealPath());
        }
        rmdir($dir);
    }
});

function writeBundle(string $dir, string $locale, string $group, array $translations): void
{
    if (! is_dir($dir.'/'.$locale)) {
        mkdir($dir.'/'.$locale, 0777, true);
    }
    file_put_contents(
        $dir.'/'.$locale.'/'.$group.'.php',
        '<?php return '.var_export($translations, true).';'
    );
}

// ─── Bundled dataset flows into the database ────────────────────────────────

it('imports bundled default-locale translations into the database', function () {
    writeBundle($this->bundleDir, 'en', 'auth', ['failed' => 'These credentials do not match.']);

    Translation::syncToDatabase();

    $row = Translation::where('group', 'auth')->where('key', 'failed')->first();

    expect($row)->not->toBeNull()
        ->and($row->text['en'])->toBe('These credentials do not match.');
});

it('imports bundled translations for an installed non-default locale', function () {
    writeBundle($this->bundleDir, 'en', 'auth', ['failed' => 'These credentials do not match.']);
    writeBundle($this->bundleDir, 'it', 'auth', ['failed' => 'Le credenziali non corrispondono.']);

    Language::factory()->create([
        'code' => 'it', 'regional' => 'it_IT', 'type' => 'locale',
        'name' => 'Italian', 'native' => 'Italiano', 'direction' => 'ltr',
        'is_default' => false, 'sort' => 2,
    ]);

    Translation::syncToDatabase();

    $row = Translation::where('group', 'auth')->where('key', 'failed')->first();

    expect($row)->not->toBeNull()
        ->and($row->text)->toHaveKeys(['en', 'it'])
        ->and($row->text['it'])->toBe('Le credenziali non corrispondono.');
});

it('does not import bundled translations for locales that are not installed', function () {
    writeBundle($this->bundleDir, 'en', 'auth', ['failed' => 'EN value']);
    writeBundle($this->bundleDir, 'de', 'auth', ['failed' => 'DE value']);

    // No Language record for 'de' and no lang/de files — the bundled
    // catalogue must NOT auto-install the locale.
    Translation::syncToDatabase();

    $row = Translation::where('group', 'auth')->where('key', 'failed')->first();

    expect($row->text)->not->toHaveKey('de')
        ->and(Language::where('code', 'de')->exists())->toBeFalse();
});

it('lets app lang files override bundled values for the same key', function () {
    writeBundle($this->bundleDir, 'en', 'auth', ['failed' => 'Bundled value']);

    mkdir($this->syncDir.'/en', 0777, true);
    file_put_contents($this->syncDir.'/en/auth.php', '<?php return ["failed" => "App value"];');

    Translation::syncToDatabase();

    $row = Translation::where('group', 'auth')->where('key', 'failed')->first();

    expect($row->text['en'])->toBe('App value');
});

it('imports the bundled dataset after a language is added via the facade', function () {
    writeBundle($this->bundleDir, 'en', 'passwords', ['reset' => 'Your password has been reset.']);
    writeBundle($this->bundleDir, 'fr', 'passwords', ['reset' => 'Votre mot de passe a été réinitialisé.']);

    Translation::syncToDatabase();

    $row = Translation::where('group', 'passwords')->where('key', 'reset')->first();
    expect($row->text)->not->toHaveKey('fr');

    Lingua::addLanguage('fr');
    Translation::syncToDatabase();

    $row = Translation::where('group', 'passwords')->where('key', 'reset')->first();
    expect($row->text['fr'])->toBe('Votre mot de passe a été réinitialisé.');
});
