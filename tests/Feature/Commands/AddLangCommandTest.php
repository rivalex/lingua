<?php

use LaravelLang\Locales\Facades\Locales;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

it('can run `lingua:add` command to add a language', function () {
    expect(Language::where('code', 'it')->exists())->toBeFalse()
        ->and(Locales::isInstalled('it'))->toBeFalse();

    $this->artisan('lingua:add', ['locale' => 'it'])
        ->assertSuccessful()
        ->expectsOutputToContain('Adding language: it')
        ->expectsOutputToContain("Language 'it' added successfully.");

    expect(Language::where('code', 'it')->exists())->toBeTrue()
        ->and(is_dir(lang_path('it')))->toBeTrue()
        ->and(file_exists(lang_path('it.json')))->toBeTrue()
        ->and(Translation::whereNotNull('text->it')->count())->toBeGreaterThan(0);

    Language::where('code', 'it')->delete();
    Artisan::call('lang:rm it --force');
});

it('syncs translations to database after adding a language', function () {
    $countBefore = Translation::count();

    $this->artisan('lingua:add', ['locale' => 'it'])
        ->assertSuccessful();

    expect(Translation::count())->toBeGreaterThanOrEqual($countBefore);

    Language::where('code', 'it')->delete();
    Artisan::call('lang:rm it --force');
});

it('outputs error when locale info fails', function () {
    $this->artisan('lingua:add', ['locale' => 'x'])
        ->assertSuccessful()
        ->expectsOutputToContain("Failed to add language 'x':");
});

it('outputs error when sync to database fails', function () {
    $this->mock(Translation::class, function ($mock) {
        $mock->shouldReceive('syncToDatabase')
            ->once()
            ->andThrow(new Exception('Error syncing to database.'));
    });

    $this->mock(Language::class, function ($mock) {
        $mock->shouldReceive('updateOrCreate')
            ->once()
            ->andReturnNull();
    });

    $this->artisan('lingua:add', ['locale' => 'it'])
        ->assertSuccessful()
        ->expectsOutputToContain("Failed to add language 'it': Error syncing to database.");

    Language::where('code', 'it')->delete();
    Artisan::call('lang:rm it --force');
});
