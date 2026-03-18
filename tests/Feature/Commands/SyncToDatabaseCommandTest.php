<?php

use Rivalex\Lingua\Models\Translation;

it('can run `lingua:sync-to-database` command', function () {
    $this->artisan('lingua:sync-to-database')
        ->assertSuccessful()
        ->expectsOutputToContain('Syncing translations from local files to database')
        ->expectsOutputToContain('Translations synced to database successfully.');
});

it('syncs translations from local files to database', function () {
    $initialCount = Translation::count();
    expect($initialCount)->toBeGreaterThan(0);

    $this->artisan('lingua:sync-to-database')
        ->assertSuccessful();

    expect(Translation::count())->toBeGreaterThanOrEqual($initialCount);
});

it('outputs error when sync to database fails', function () {
    $this->mock(Translation::class, function ($mock) {
        $mock->shouldReceive('syncToDatabase')
            ->once()
            ->andThrow(new Exception('Database connection failed.'));
    });

    $this->artisan('lingua:sync-to-database')
        ->assertSuccessful()
        ->expectsOutputToContain('Failed to sync translations to database: Database connection failed.');
});
