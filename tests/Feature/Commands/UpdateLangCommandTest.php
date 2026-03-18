<?php

use Rivalex\Lingua\Models\Translation;

it('can run `lingua:update-lang` command', function () {
    $this->artisan('lingua:update-lang')
        ->assertSuccessful()
        ->expectsOutputToContain('Updating language files via Laravel Lang')
        ->expectsOutputToContain('Translations updated and synced to database successfully.');
});

it('calls lang:update and then syncs to database', function () {
    $initialCount = Translation::count();
    expect($initialCount)->toBeGreaterThan(0);

    $this->artisan('lingua:update-lang')
        ->assertSuccessful();

    expect(Translation::count())->toBeGreaterThanOrEqual($initialCount);
});

it('outputs error when sync to database fails after lang:update', function () {
    $this->mock(Translation::class, function ($mock) {
        $mock->shouldReceive('syncToDatabase')
            ->once()
            ->andThrow(new Exception('Sync to database failed.'));
    });

    $this->artisan('lingua:update-lang')
        ->assertSuccessful()
        ->expectsOutputToContain('Failed to update language files: Sync to database failed.');
});
