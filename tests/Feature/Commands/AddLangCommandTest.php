<?php

declare(strict_types=1);

use Rivalex\Lingua\Contracts\TranslationRepository;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

it('can run `lingua:add` command to add a language', function () {
    expect(Language::where('code', 'it')->exists())->toBeFalse();

    $this->artisan('lingua:add', ['locale' => 'it'])
        ->assertSuccessful()
        ->expectsOutputToContain('Adding language: it')
        ->expectsOutputToContain("Language 'it' added successfully.");

    expect(Language::where('code', 'it')->exists())->toBeTrue();

    Language::where('code', 'it')->delete();
});

it('syncs translations to database after adding a language', function () {
    $countBefore = Translation::count();

    $this->artisan('lingua:add', ['locale' => 'it'])
        ->assertSuccessful();

    expect(Translation::count())->toBeGreaterThanOrEqual($countBefore);

    Language::where('code', 'it')->delete();
});

it('outputs error when locale info fails', function () {
    $this->artisan('lingua:add', ['locale' => 'x'])
        ->assertSuccessful()
        ->expectsOutputToContain("Failed to add language 'x':");
});

it('outputs error when install locale fails', function () {
    $this->mock(TranslationRepository::class, function ($mock) {
        $mock->shouldReceive('installLocale')
            ->once()
            ->andThrow(new Exception('Error syncing to database.'));
    });

    $this->artisan('lingua:add', ['locale' => 'it'])
        ->assertSuccessful()
        ->expectsOutputToContain("Failed to add language 'it': Error syncing to database.");

    Language::where('code', 'it')->delete();
});
