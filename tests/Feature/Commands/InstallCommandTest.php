<?php

declare(strict_types=1);

it('lingua:install with database driver shows env instruction and no file-mode warnings', function () {
    $this->artisan('lingua:install')
        ->expectsChoice('Translation storage driver?', 'database', ['database', 'file'])
        ->expectsConfirmation('Would you like to run the migrations now?', 'no')
        ->expectsConfirmation('Would you like to star our repo on GitHub?', 'no')
        ->expectsOutputToContain('Set LINGUA_STORAGE_DRIVER=database in your .env')
        ->assertSuccessful();
});

it('lingua:install with file driver shows four file-mode warnings', function () {
    $this->artisan('lingua:install')
        ->expectsChoice('Translation storage driver?', 'file', ['database', 'file'])
        ->expectsConfirmation('Would you like to run the migrations now?', 'no')
        ->expectsConfirmation('Would you like to star our repo on GitHub?', 'no')
        ->expectsOutputToContain('Set LINGUA_STORAGE_DRIVER=file in your .env')
        ->expectsOutputToContain('FILE DRIVER: translations are written directly to lang/.')
        ->expectsOutputToContain('Your deploy pipeline (Forge/Envoyer/CI) may overwrite these files')
        ->expectsOutputToContain('or fail on a dirty working tree. Commit lang/ changes deliberately.')
        ->expectsOutputToContain('See docs: Storage Drivers > File mode caveats.')
        ->assertSuccessful();
});
