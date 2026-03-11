<?php

use Illuminate\Support\Facades\Artisan;
use LaravelLang\Locales\Facades\Locales;
use Livewire\Livewire;
use Rivalex\Lingua\Livewire\Language\Create;
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
    expect(Language::where('code', 'it')->exists())->toBeFalse()
        ->and(Locales::isInstalled('it'))->toBeFalse()
        ->and(Translation::whereNotNull('text->it')->count())
        ->toBe(0);

    Livewire::test(Create::class)
        ->set('language', 'it')
        ->assertSet('language', 'it')
        ->call('addNewLanguage')
        ->assertHasNoErrors('language')
        ->assertDispatched('refreshLanguages')
        ->assertDispatched('language_added')
        ->assertSet('language', '');

    expect(Language::where('code', 'it')->exists())->toBeTrue()
        ->and(is_dir(lang_path('it')))->toBeTrue()
        ->and(file_exists(lang_path('it.json')))->toBeTrue()
        ->and(Translation::whereNotNull('text->it')->count())
        ->toBeGreaterThan(0);

    $language = Language::where('code', 'it')->first();
    $stringsCount = Translation::where('text->'.$language->code)->count();

    Livewire::test(Row::class, ['languageId' => $language->id])
        ->assertStatus(200)
        ->assertSee($language->native)
        ->assertSee($language->name)
        ->assertSee($stringsCount)
        ->assertSee('Set as DEFAULT');

    Language::where('code', 'it')->delete();
    Artisan::call('lang:rm it --force');
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
