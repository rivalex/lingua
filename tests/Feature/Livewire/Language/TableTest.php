<?php

declare(strict_types=1);

use Livewire\Livewire;
use Rivalex\Lingua\Livewire\Language\Table;
use Rivalex\Lingua\Locales\LocaleRegistry;
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

    $registry = app(LocaleRegistry::class);
    foreach (['it', 'es'] as $code) {
        $data = $registry->info($code);
        Language::create([
            'code' => $data->code,
            'regional' => $data->regional,
            'type' => $data->type,
            'name' => $data->name,
            'native' => $data->native,
            'direction' => $data->direction,
            'is_default' => false,
        ]);
    }

    assertDatabaseHas('languages', ['code' => 'it']);
    assertDatabaseHas('languages', ['code' => 'es']);

    Livewire::test(Table::class)
        ->assertCount('languages', $count + 2);

    Language::where('code', 'it')->delete();
    Language::where('code', 'es')->delete();
});

it('can `SEARCH/FILTER` languages by code, regional, name and native', function () {
    $registry = app(LocaleRegistry::class);

    $it = $registry->info('it');
    Language::create([
        'code' => $it->code,
        'regional' => $it->regional,
        'type' => $it->type,
        'name' => $it->name,
        'native' => $it->native,
        'direction' => $it->direction,
        'is_default' => false,
    ]);

    $ar = $registry->info('ar');
    Language::create([
        'code' => $ar->code,
        'regional' => $ar->regional,
        'type' => $ar->type,
        'name' => $ar->name,
        'native' => $ar->native,
        'direction' => $ar->direction,
        'is_default' => false,
    ]);

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
