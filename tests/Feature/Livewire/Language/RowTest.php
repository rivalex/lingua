<?php

declare(strict_types=1);

use Livewire\Livewire;
use Rivalex\Lingua\Livewire\Language\Row;
use Rivalex\Lingua\Livewire\Language\SetDefault;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

it('renders a `LANGUAGE ROW` with statistics for `default Language`', function () {
    $language = Language::first();
    $stringsCount = Translation::where('text->'.$language->code)->count();

    Livewire::test(Row::class, ['languageId' => $language->id])
        ->assertStatus(200)
        ->assertSee($language->native)
        ->assertSee($language->name)
        ->assertSee($stringsCount)
        ->assertDontSee('Set as DEFAULT');
});

it('renders a `LANGUAGE ROW` with statistics for `NON default Languages`', function () {
    expect(Language::where('code', 'it')->exists())->toBeFalse();

    $language = Language::factory()->create([
        'code' => 'it',
        'is_default' => false,
    ]);

    expect(Language::where('code', 'it')->exists())->toBeTrue();

    $stringsCount = Translation::where('text->'.$language->code)->count();

    Livewire::test(Row::class, ['languageId' => $language->id])
        ->assertStatus(200)
        ->assertSee($language->native)
        ->assertSee($language->name)
        ->assertSee($stringsCount)
        ->assertSee('Set as DEFAULT');

    Language::where('code', 'it')->delete();
});

it('react on `refreshLanguageRows` event dispatched', function () {
    $newDefault = Language::factory()->create([
        'code' => 'it',
        'is_default' => false,
    ]);

    $component = Livewire::test(Row::class, ['languageId' => $newDefault->id]);
    $component->assertSee('Set as DEFAULT');

    Livewire::test(SetDefault::class, ['language' => $newDefault])
        ->call('setDefaultLanguage');

    $component->dispatch('refreshLanguageRows')
        ->assertDontSee('Set as DEFAULT');
});
