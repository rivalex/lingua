<?php

use Livewire\Livewire;
use Rivalex\Lingua\Livewire\Language\Sort;
use Rivalex\Lingua\Livewire\Languages;
use Rivalex\Lingua\Models\Language;

it('can reach the `LANGUAGE SORT` component', function () {
    Livewire::test(Sort::class)
            ->assertStatus(200);
});

it('can read the `COMPUTED` property `languages`', function () {
    $count = Language::count();
    Livewire::test(Sort::class)
            ->assertCount('languages', $count);

    Language::factory()->create([
        'code' => 'it',
        'is_default' => false,
    ]);

    Livewire::test(Sort::class)
            ->assertCount('languages', $count + 1);
    Language::where('code', 'it')->delete();
});

it('can `SORT` languages order', function () {
    Language::factory()->create([
        'code' => 'it',
        'is_default' => false,
    ]);

    $languages = Language::orderBy('sort')->get();
    $target = $languages->first();

    expect($target->sort === 1)->toBeTrue()
                               ->and($target->code === 'en');

    Livewire::test(Sort::class)
            ->call('updateLanguageOrder', $target->id, 0)
            ->assertDispatched('languages_sorted')
            ->assertDispatched('refreshLanguages')
            ->assertDispatched('refreshLanguageSelector');

    $languages = Language::orderBy('sort')->get();
    $target = $languages->first();
    expect($target->sort === 1)->toBeTrue()
                               ->and($target->code === 'it');

    Language::where('code', 'it')->delete();
});

it('catch `ERRORS` on sort languages', function () {
    $target = Language::first() ?? Language::factory()->create();

    Livewire::test(Sort::class)
            ->call('updateLanguageOrder', $target->id, ['invalid'])
            ->assertHasErrors('updateLanguageOrderError')
            ->assertDispatched('languages_sorted_fail');
});

it('can react on `refreshLanguages event` dispatched', function () {
    $component = Livewire::test(Languages::class);
    $component->assertDontSeeHtml('Sort Languages');

    Language::factory()->create([
        'code' => 'it',
        'is_default' => false,
    ]);

    $component->dispatch('refreshLanguages')
              ->assertSeeHtml('Sort Languages');

    Language::where('code', 'it')->delete();
});
