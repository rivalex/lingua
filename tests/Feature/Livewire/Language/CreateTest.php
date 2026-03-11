<?php

use Illuminate\Contracts\Console\Kernel;
use LaravelLang\Locales\Facades\Locales;
use Livewire\Livewire;
use Rivalex\Lingua\Livewire\Language\Create;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

it('can render `CREATE` language component', function () {
    Livewire::test(Create::class)
            ->assertStatus(200)
            ->assertSeeHtml('Add new Language');
});

it('can initialize `availableLanguages` properties', function () {
    $availableLanguages = count(Locales::available()) - count(Locales::installed());
    Livewire::test(Create::class)
            ->set('availableLanguages', [])
            ->assertCount('availableLanguages', 0)
            ->call('refreshLanguages')
            ->assertCount('availableLanguages', $availableLanguages);
});

it('can add new language with `addNewLanguage` method', function () {
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


    Language::where('code', 'it')->delete();
    \Illuminate\Support\Facades\Artisan::call('lang:rm it --force');
});

it('catch `Validation ERRORS` on `addNewLanguage`', function () {
    $locale = '';
    Livewire::test(Create::class)
            ->set('language', $locale)
            ->assertSet('language', $locale)
            ->call('addNewLanguage')
            ->assertHasErrors(['language']);
});

it('catch `ERRORS` on `addNewLanguage` for `Locale::info($locale)`', function () {

    $locale = 'x';
    Livewire::test(Create::class)
            ->set('language', $locale)
            ->assertSet('language', $locale)
            ->call('addNewLanguage')
            ->assertHasNoErrors('language')
            ->assertHasErrors(['addLanguageError'])
            ->assertDispatched('language_added_fail')
            ->assertSet('language', '');
});

it('catch `ERRORS` on `addNewLanguage` for `Artisan::call(\'lang:add it\')`', function () {
    $locale = 'it';
    $originalKernel = app(Kernel::class);
    Artisan::swap(
        Mockery::mock(Kernel::class, function ($mock) {
            $mock->shouldReceive('call')
                 ->once()
                 ->with('lang:add it')
                 ->andThrow(new Exception('Artisan command failed.'));
        })
    );

    try {
        Livewire::test(Create::class)
                ->set('language', $locale)
                ->assertSet('language', $locale)
                ->call('addNewLanguage')
                ->assertHasNoErrors('language')
                ->assertHasErrors(['addLanguageError'])
                ->assertDispatched('language_added_fail')
                ->assertSet('language', '');
    } finally {
        Artisan::swap($originalKernel);
    }
});


it('catch `ERRORS` on `addNewLanguage` for `Language::create()`', function () {
    $this->mock(Language::class, function ($mock) {
        $mock->shouldReceive('create')
             ->once()
             ->andThrow(new Exception('Error creating language.'));
    });

    Livewire::test(Create::class)
            ->set('language', 'it')
            ->assertSet('language', 'it')
            ->call('addNewLanguage')
            ->assertHasErrors(['addLanguageError'])
            ->assertDispatched('language_added_fail')
            ->assertSet('language', '');
});

it('catch `ERRORS` on `addNewLanguage` for `Translation::syncToDatabase()`', function () {
    $this->mock(Translation::class, function ($mock) {
        $mock->shouldReceive('syncToDatabase')
             ->once()
             ->andThrow(new Exception('Error syncing'));
    });

    $this->mock(Language::class, function ($mock) {
        $mock->shouldReceive('create')
             ->once()
             ->andReturnNull();
    });

    $originalKernel = app(Kernel::class);
    Artisan::swap(
        Mockery::mock(Kernel::class, function ($mock) {
            $mock->shouldReceive('call')
                 ->once()
                 ->with('lang:add it')
                 ->andReturnNull();
        })
    );

    try {
        Livewire::test(Create::class)
                ->set('language', 'it')
                ->assertSet('language', 'it')
                ->call('addNewLanguage')
                ->assertHasErrors(['addLanguageError'])
                ->assertDispatched('language_added_fail')
                ->assertSet('language', '');
    } finally {
        Artisan::swap($originalKernel);
    }

    Language::where('code', 'it')->delete();
    \Illuminate\Support\Facades\Artisan::call('lang:rm it --force');
});

it('react on `refreshLanguages event` dispatched', function () {
    $availableLanguages = count(Locales::available()) - count(Locales::installed());
    Livewire::test(Create::class)
            ->set('availableLanguages', [])
            ->assertCount('availableLanguages', 0)
            ->dispatch('refreshLanguages')
            ->assertCount('availableLanguages', $availableLanguages);
});
