<?php

use LaravelLang\Locales\Facades\Locales;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

beforeEach(function () {
    if (! Language::where('code', 'it')->exists()) {
        Artisan::call('lingua:add', ['locale' => 'it']);
    }
});

afterEach(function () {
    Language::where('code', 'it')->delete();
    Artisan::call('lang:rm it --force');
});

it('can run `lingua:remove` command to remove a language', function () {
    expect(Language::where('code', 'it')->exists())->toBeTrue()
        ->and(Translation::whereNotNull('text->it')->count())->toBeGreaterThan(0);

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
    $translationsBefore = Translation::whereNotNull('text->it')->count();
    expect($translationsBefore)->toBeGreaterThan(0);

    $this->artisan('lingua:remove', ['locale' => 'it'])
        ->assertSuccessful();

    expect(Translation::whereNotNull('text->it')->count())->toBe(0);
});

it('outputs error when sync to database fails on remove', function () {
    $this->mock(Translation::class, function ($mock) {
        $mock->shouldReceive('syncToDatabase')
            ->once()
            ->andThrow(new Exception('Sync failed during remove.'));
    });

    $this->artisan('lingua:remove', ['locale' => 'it'])
        ->assertSuccessful()
        ->expectsOutputToContain("Failed to remove language 'it': Sync failed during remove.");
});
