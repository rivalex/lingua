<?php

use LaravelLang\Locales\Facades\Locales;
use Livewire\Livewire;
use Rivalex\Lingua\Livewire\Language\Table;
use Rivalex\Lingua\Models\Language;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

it('can render `TABLE component`', function () {
    $component = Livewire::test(Table::class);
    $component->assertStatus(200)
        ->assertSeeHtml('Languages');
});

it('can access to `COMPUTED` property `languages`', function () {
    assertDatabaseMissing('languages', ['code' => 'it']);
    assertDatabaseMissing('languages', ['code' => 'es']);

    $count = Language::count();

    Livewire::test(Table::class)
        ->assertCount('languages', $count);

    Language::factory()->create([
        'code' => 'it',
        'is_default' => false,
    ]);
    Language::factory()->create([
        'code' => 'es',
        'is_default' => false,
    ]);

    assertDatabaseHas('languages', ['code' => 'it']);
    assertDatabaseHas('languages', ['code' => 'es']);

    Livewire::test(Table::class)
        ->assertCount('languages', $count + 2);

    Language::where('code', 'it')->delete();
    Language::where('code', 'es')->delete();
});

it('can `SEARCH/FILTER` languages by code, regional, name and native', function () {

    $localeData = Locales::info('it');
    Language::create(
        [
            'code' => $localeData->code,
            'regional' => $localeData->regional,
            'type' => $localeData->type,
            'name' => $localeData->localized,
            'native' => $localeData->native,
            'direction' => $localeData->direction->value,
            'is_default' => false,
        ]
    );

    $localeData = Locales::info('ar');
    Language::create(
        [
            'code' => $localeData->code,
            'regional' => $localeData->regional,
            'type' => $localeData->type,
            'name' => $localeData->localized,
            'native' => $localeData->native,
            'direction' => $localeData->direction->value,
            'is_default' => false,
        ]
    );

    Livewire::test(Table::class)
        ->set('search', 'english')
        ->assertCount('languages', 1);

    Livewire::test(Table::class)
        ->set('search', 'italian')
        ->assertCount('languages', 1);

    Livewire::test(Table::class)
        ->set('search', 'العربية')
        ->assertCount('languages', 1);

    Language::where('code', 'it')->delete();
    Language::where('code', 'ar')->delete();
});

it('can react on `refreshLanguages event` dispatched', function () {
    $componentId = Livewire::test(Table::class)->id();
    Livewire::test(Table::class)
        ->dispatch('refreshLanguages')
        ->assertDontSeeHtml($componentId);
});
