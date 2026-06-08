<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Rivalex\Lingua\Livewire\Language\Row;
use Rivalex\Lingua\Livewire\Statistics;
use Rivalex\Lingua\Livewire\Translations;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

// ---------------------------------------------------------------------------
// Config defaults
// ---------------------------------------------------------------------------

it('has links.translations.enabled set to true by default', function () {
    expect(config('lingua.links.translations.enabled'))->toBeTrue();
});

it('has links.translations.route set to lingua.translations by default', function () {
    expect(config('lingua.links.translations.route'))->toBe('lingua.translations');
});

// ---------------------------------------------------------------------------
// Language Row — enabled toggle
// ---------------------------------------------------------------------------

it('row renders a link to translations by default', function () {
    $language = Language::first();

    Livewire::test(Row::class, ['languageId' => $language->id])
        ->assertSeeHtml('href=');
});

it('row renders plain text when translations_link is disabled', function () {
    config(['lingua.links.translations.enabled' => false]);

    $language = Language::first();

    Livewire::test(Row::class, ['languageId' => $language->id])
        ->assertSee($language->native)
        ->assertDontSeeHtml('lingua/translations')
        ->assertDontSeeHtml('flux:link');
});

// ---------------------------------------------------------------------------
// Language Row — custom route
// ---------------------------------------------------------------------------

it('row link uses a custom route name when configured', function () {
    Route::get('my-translations/{locale}', fn () => 'ok')
        ->name('custom.translations');
    config(['lingua.links.translations.route' => 'custom.translations']);

    $language = Language::first();

    Livewire::test(Row::class, ['languageId' => $language->id])
        ->assertSeeHtml('my-translations/'.$language->code);
});

// ---------------------------------------------------------------------------
// Language Row — wire:navigate follows global navigate config
// ---------------------------------------------------------------------------

it('row link does not carry wire:navigate when navigate is false', function () {
    config(['lingua.navigate' => false, 'lingua.links.translations.enabled' => true]);

    $language = Language::first();

    Livewire::test(Row::class, ['languageId' => $language->id])
        ->assertDontSeeHtml('wire:navigate');
});

it('row link carries wire:navigate when navigate is true', function () {
    config(['lingua.navigate' => true, 'lingua.links.translations.enabled' => true]);

    $language = Language::first();

    Livewire::test(Row::class, ['languageId' => $language->id])
        ->assertSeeHtml('wire:navigate');
});

// ---------------------------------------------------------------------------
// Statistics missing-keys panel
// ---------------------------------------------------------------------------

it('statistics missing panel shows translate link by default', function () {
    Language::factory()->create(['code' => 'it', 'is_default' => false]);
    Translation::create([
        'group' => 'test',
        'key' => 'missing_key',
        'text' => ['en' => 'Only English'],
        'type' => 'text',
        'is_vendor' => false,
    ]);

    Livewire::test(Statistics::class)
        ->call('toggleMissingKeys', 'it')
        ->assertSeeHtml('<a')
        ->assertSeeHtml('lingua/translations');

    Language::where('code', 'it')->delete();
    Translation::where('key', 'missing_key')->where('group', 'test')->delete();
});

it('statistics missing panel shows plain label when translations_link is disabled', function () {
    config(['lingua.links.translations.enabled' => false]);

    Language::factory()->create(['code' => 'it', 'is_default' => false]);
    Translation::create([
        'group' => 'test',
        'key' => 'missing_key',
        'text' => ['en' => 'Only English'],
        'type' => 'text',
        'is_vendor' => false,
    ]);

    Livewire::test(Statistics::class)
        ->call('toggleMissingKeys', 'it')
        ->assertDontSeeHtml('<a')
        ->assertSeeHtml('<span');

    Language::where('code', 'it')->delete();
    Translation::where('key', 'missing_key')->where('group', 'test')->delete();
});

// ---------------------------------------------------------------------------
// Translations component — updatedCurrentLocale uses configured route
// ---------------------------------------------------------------------------

it('Translations locale change redirects to custom route when configured', function () {
    Route::get('admin/translations/{locale}', fn () => 'ok')
        ->name('admin.translations');
    config(['lingua.links.translations.route' => 'admin.translations']);

    Language::factory()->create(['code' => 'fr', 'is_default' => false]);

    Livewire::test(Translations::class)
        ->set('currentLocale', 'fr')
        ->assertRedirectContains('admin/translations/fr');

    Language::where('code', 'fr')->delete();
});
