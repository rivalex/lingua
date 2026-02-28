<?php

use Livewire\Livewire;
use Rivalex\Lingua\Livewire\Language\Sort;
use Rivalex\Lingua\Models\Language;

it('can reach the language sort component page', function () {
    Livewire::test(Sort::class)
        ->assertStatus(200);
});

it('can compute the languages property', function () {
    Language::factory()->count(1)->create();
    $count = Language::count();
    Livewire::test(Sort::class)
        ->assertCount('languages', $count);
});

it('can sort languages', function () {
    $languages = Language::orderBy('sort')->get();
    $target = $languages->get(1) ?? $languages->first();

    Livewire::test(Sort::class)
        ->call('updateLanguageOrder', $target->id, 0)
        ->assertDispatched('languages_sorted')
        ->assertDispatched('refreshLanguages')
        ->assertDispatched('refreshLanguageSelector');
});

it('can catch ERRORS on sort languages', function () {
    $target = Language::first() ?? Language::factory()->create();

    Livewire::test(Sort::class)
        ->call('updateLanguageOrder', $target->id, ['invalid'])
        ->assertHasErrors('updateLanguageOrder')
        ->assertDispatched('languages_sorted_fail');
});
