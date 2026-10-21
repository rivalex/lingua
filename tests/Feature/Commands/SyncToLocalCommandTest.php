<?php

use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

it('can run `lingua:sync-to-local` command', function () {
    $this->artisan('lingua:sync-to-local')
        ->assertSuccessful()
        ->expectsOutputToContain('Syncing translations from database to local files')
        ->expectsOutputToContain('Translations synced to local files successfully.');
});

it('exports translations from database to local files', function () {
    $language = Language::where('is_default', true)->first();
    expect($language)->not->toBeNull();

    $this->artisan('lingua:sync-to-local')
        ->assertSuccessful();

    $locale = $language->code;
    expect(file_exists(lang_path($locale.'.json')))->toBeTrue();
});

it('outputs error when sync to local fails', function () {
    $this->mock(Translation::class, function ($mock) {
        $mock->shouldReceive('syncToLocal')
            ->once()
            ->andThrow(new Exception('Disk write error.'));
    });

    $this->artisan('lingua:sync-to-local')
        ->assertSuccessful()
        ->expectsOutputToContain('Failed to sync translations to local files: Disk write error.');
});

// §8.7 — Guard sync anti-overwrite in file-mode

it('lingua:sync-to-local in file-mode without --force is a no-op with warning', function () {
    config(['lingua.storage.driver' => 'file']);

    $this->artisan('lingua:sync-to-local')
        ->expectsOutputToContain('Refusing: file-mode active')
        ->assertSuccessful();
});

it('lingua:sync-to-local in file-mode with --force and confirm proceeds', function () {
    config(['lingua.storage.driver' => 'file']);

    $this->artisan('lingua:sync-to-local', ['--force' => true])
        ->expectsConfirmation('File-mode is active. DB may be empty and overwrite your files. Proceed?', 'yes')
        ->expectsOutputToContain('Syncing translations from database to local files')
        ->assertSuccessful();
});
