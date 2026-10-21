<?php

declare(strict_types=1);

use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

beforeEach(function () {
    if (! Language::where('code', 'it')->exists()) {
        Artisan::call('lingua:add', ['locale' => 'it']);
    }
});

afterEach(function () {
    Language::where('code', 'it')->delete();
});

it('can run `lingua:remove` command to remove a language', function () {
    expect(Language::where('code', 'it')->exists())->toBeTrue();

    $this->artisan('lingua:remove', ['locale' => 'it'])
        ->assertSuccessful()
        ->expectsOutputToContain('Removing language: it')
        ->expectsOutputToContain("Language 'it' removed successfully.");

    expect(Language::where('code', 'it')->exists())->toBeFalse()
        ->and(Translation::whereNotNull('text->it')->count())->toBe(0);
});

it('warns when locale is not found in database but continues', function () {
    Language::where('code', 'it')->delete();

    $this->artisan('lingua:remove', ['locale' => 'it'])
        ->assertSuccessful()
        ->expectsOutputToContain("Language 'it' was not found in the database.");
});

it('prevents removing the default language', function () {
    $default = Language::where('is_default', true)->first();

    $this->artisan('lingua:remove', ['locale' => $default->code])
        ->assertSuccessful()
        ->expectsOutputToContain("Cannot remove the default language '{$default->code}'");
});

it('cleans up translations when removing a language', function () {
    $this->artisan('lingua:remove', ['locale' => 'it'])
        ->assertSuccessful();

    expect(Translation::whereNotNull('text->it')->count())->toBe(0);
});

it('does not re-import the removed language from lang files (no resurrect)', function () {
    // Give the locale real lang files: the old implementation ran a full
    // syncToDatabase() after the removal, which re-imported the locale from
    // these files and recreated its Language record — undoing the removal.
    $syncDir = sys_get_temp_dir().'/lingua_remove_'.str_replace('.', '_', uniqid('', true));
    mkdir($syncDir.'/en', 0777, true);
    mkdir($syncDir.'/it', 0777, true);
    file_put_contents($syncDir.'/en/ui.php', '<?php return ["hello" => "Hello"];');
    file_put_contents($syncDir.'/it/ui.php', '<?php return ["hello" => "Ciao"];');
    config(['lingua.lang_dir' => $syncDir]);

    Artisan::call('lingua:sync-to-database');
    expect(Translation::whereNotNull('text->it')->count())->toBeGreaterThan(0);

    $this->artisan('lingua:remove', ['locale' => 'it'])->assertSuccessful();

    expect(Language::where('code', 'it')->exists())->toBeFalse()
        ->and(Translation::whereNotNull('text->it')->count())->toBe(0);

    // Cleanup
    foreach ([$syncDir.'/en/ui.php', $syncDir.'/it/ui.php'] as $file) {
        unlink($file);
    }
    rmdir($syncDir.'/en');
    rmdir($syncDir.'/it');
    rmdir($syncDir);
});

it('rejects a malformed locale argument before touching the database', function () {
    $this->artisan('lingua:remove', ['locale' => 'it"; DROP TABLE x;--'])
        ->assertSuccessful()
        ->expectsOutputToContain('Invalid locale format');
});
