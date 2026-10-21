<?php

declare(strict_types=1);

use Rivalex\Lingua\Models\Translation;

// §8.5 — Switch DB→file with confirmation (lingua:storage)

it('lingua:storage with same driver outputs already-set and exits 0', function (): void {
    config(['lingua.storage.driver' => 'database']);

    $this->artisan('lingua:storage', ['driver' => 'database'])
        ->expectsOutputToContain("Driver already set to 'database'")
        ->assertSuccessful();
});

it('lingua:storage with invalid driver exits with failure', function (): void {
    $this->artisan('lingua:storage', ['driver' => 'redis'])
        ->expectsOutputToContain("Invalid driver 'redis'")
        ->assertFailed();
});

it('lingua:storage file warns about type loss and aborts when user declines', function (): void {
    config(['lingua.storage.driver' => 'database']);

    Translation::create([
        'group' => 'cmd_test', 'key' => 'html_'.uniqid(),
        'type' => 'html', 'text' => ['en' => '<p>Hello</p>'],
        'is_vendor' => false, 'vendor' => null,
    ]);

    $this->artisan('lingua:storage', ['driver' => 'file'])
        ->expectsOutputToContain('html/markdown')
        ->expectsConfirmation('Proceed?', 'no')
        ->expectsOutputToContain('Aborted.')
        ->assertSuccessful();
});

it('lingua:storage file with --force skips confirmation and prints driver instruction', function (): void {
    config(['lingua.storage.driver' => 'database']);

    $this->artisan('lingua:storage', ['driver' => 'file', '--force' => true])
        ->expectsOutputToContain('Syncing translations to local files')
        ->expectsOutputToContain('LINGUA_STORAGE_DRIVER=file')
        ->assertSuccessful();
});

// §8.6 — Switch file→database

it('lingua:storage database from file-mode syncs files to DB and prints driver instruction', function (): void {
    config(['lingua.storage.driver' => 'file']);

    $this->artisan('lingua:storage', ['driver' => 'database'])
        ->expectsOutputToContain('Syncing translations from local files to database')
        ->expectsOutputToContain('LINGUA_STORAGE_DRIVER=database')
        ->assertSuccessful();
});
