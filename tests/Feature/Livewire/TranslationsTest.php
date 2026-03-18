<?php

use Livewire\Livewire;
use Rivalex\Lingua\Livewire\Translations;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

it('can reach the translations component page', function () {
    Livewire::test(Translations::class)
        ->assertStatus(200);
});

it('can render `TRANSLATIONS` component with default locale', function () {
    Livewire::test(Translations::class)
        ->assertStatus(200)
        ->assertSet('currentLocale', linguaDefaultLocale());
});

it('can render `TRANSLATIONS` component with specific locale', function () {
    Language::factory()->create(['code' => 'it', 'is_default' => false]);

    Livewire::test(Translations::class, ['locale' => 'it'])
        ->assertStatus(200)
        ->assertSet('currentLocale', 'it');

    Language::where('code', 'it')->delete();
});

it('populates `availableLocale` and `availableGroups` on mount', function () {
    $component = Livewire::test(Translations::class);
    $component->assertNotSet('availableLocale', [])
              ->assertNotSet('availableGroups', []);
});

it('can `SEARCH` translations by key without errors', function () {
    $group = Translation::first()->group;

    Livewire::test(Translations::class)
        ->set('search', $group)
        ->assertStatus(200)
        ->assertSet('search', $group);
});

it('can `FILTER` by group without errors', function () {
    $group = Translation::first()->group;

    Livewire::test(Translations::class)
        ->set('group', $group)
        ->assertStatus(200)
        ->assertSet('group', $group);
});

it('`updatedGroup` resets page and dispatches `updateTranslationGroup` event', function () {
    $group = Translation::first()->group;

    Livewire::test(Translations::class)
        ->set('group', $group)
        ->assertDispatched('updateTranslationGroup', $group);
});

it('`showOnlyMissing` filter is set correctly', function () {
    Language::factory()->create(['code' => 'it', 'is_default' => false]);

    Livewire::test(Translations::class, ['locale' => 'it'])
        ->set('showOnlyMissing', true)
        ->assertStatus(200)
        ->assertSet('showOnlyMissing', true);

    Language::where('code', 'it')->delete();
});

it('`updatedCurrentLocale` resets `showOnlyMissing`', function () {
    Language::factory()->create(['code' => 'it', 'is_default' => false]);

    Livewire::test(Translations::class)
        ->set('showOnlyMissing', true)
        ->set('currentLocale', 'it')
        ->assertSet('showOnlyMissing', false);

    Language::where('code', 'it')->delete();
});

it('responds to `refreshTranslationsTableDefaults` event', function () {
    Livewire::test(Translations::class)
        ->dispatch('refreshTranslationsTableDefaults')
        ->assertStatus(200);
});

it('respects `perPage` setting', function () {
    Livewire::test(Translations::class)
        ->assertSet('perPage', 10)
        ->set('perPage', 25)
        ->assertSet('perPage', 25);
});

it('`translations` computed property returns only filtered results', function () {
    $total = Translation::count();

    // A search that matches nothing should return 0
    $component = Livewire::test(Translations::class)
        ->set('search', '__no_match_possible_xyz_123__');

    $translations = $component->instance()->translations();
    expect($translations->total())->toBe(0);

    // No search returns all
    $component2 = Livewire::test(Translations::class)
        ->set('search', '');

    expect($component2->instance()->translations()->total())->toBe($total);
});
