<?php

declare(strict_types=1);

use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

it('can run `lingua:update-lang` command', function () {
    $this->artisan('lingua:update-lang')
        ->assertSuccessful()
        ->expectsOutputToContain('Updating languages')
        ->expectsOutputToContain('Translations updated and synced to database successfully.');
});

it('syncs translations to database after updating languages', function () {
    $initialCount = Translation::count();
    expect($initialCount)->toBeGreaterThan(0);

    $this->artisan('lingua:update-lang')
        ->assertSuccessful();

    expect(Translation::count())->toBeGreaterThanOrEqual($initialCount);
});

it('skips update when no languages are installed', function () {
    Language::query()->delete();

    $this->artisan('lingua:update-lang')
        ->assertSuccessful()
        ->expectsOutputToContain('No languages installed. Skipping update.');
});

it('outputs error when sync to database fails during update', function () {
    $this->mock(Translation::class, function ($mock) {
        $mock->shouldReceive('syncToDatabase')
            ->once()
            ->andThrow(new Exception('Sync to database failed.'));
    });

    $this->artisan('lingua:update-lang')
        ->assertSuccessful()
        ->expectsOutputToContain('Failed to update languages: Sync to database failed.');
});
