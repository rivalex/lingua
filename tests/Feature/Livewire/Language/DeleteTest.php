<?php

use Illuminate\Contracts\Console\Kernel;
use LaravelLang\Locales\Facades\Locales;
use Livewire\Livewire;
use Rivalex\Lingua\Livewire\Language\Create;
use Rivalex\Lingua\Livewire\Language\Delete;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

it('can `delete language`', function () {
    expect(Language::where('code', 'it')->exists())->toBeFalse()
                                                   ->and(Locales::isInstalled('it'))->toBeFalse()
                                                   ->and(file_exists(lang_path('it.json')))->toBeFalse()
                                                   ->and(Translation::whereNotNull('text->it')->count())->toBe(0);

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
    $component = Livewire::test(Delete::class, ['language' => $language]);

    $component
        ->assertSet('language', $language)
        ->set('control', $component->get('confirm'))
        ->call('deleteLanguage')
        ->assertHasNoErrors('control')
        ->assertDispatched('refreshLanguages');

    expect(Language::where('code', 'it')->exists())->toBeFalse()
                                                   ->and(is_dir(lang_path('it')))->toBeFalse()
                                                   ->and(file_exists(lang_path('it.json')))->toBeFalse()
                                                   ->and(Translation::whereNotNull('text->it')->count())
                                                   ->toBe(0);

    Language::where('code', 'it')->delete();
    \Illuminate\Support\Facades\Artisan::call('lang:rm it --force');
});

it('catch `Validation ERRORS` on `deleteLanguage`', function () {
    Livewire::test(Create::class)
            ->set('language', 'it')
            ->assertSet('language', 'it')
            ->call('addNewLanguage')
            ->assertHasNoErrors('language')
            ->assertDispatched('refreshLanguages')
            ->assertDispatched('language_added')
            ->assertSet('language', '');

    $language = Language::where('code', 'it')->first();
    Livewire::test(Delete::class, ['language' => $language])
            ->assertSet('language', $language)
            ->set('control', '')
            ->call('deleteLanguage')
            ->assertHasErrors(['control']);

    Language::where('code', 'it')->delete();
    \Illuminate\Support\Facades\Artisan::call('lang:rm it --force');
});

it('catch `ERRORS` on `deleteLanguage` for `Artisan::call`', function () {
    $locale = 'it';
    $artisanCommand = 'lang:rm ' . strtolower($locale) . ' --force';

    Livewire::test(Create::class)
            ->set('language', $locale)
            ->assertSet('language', $locale)
            ->call('addNewLanguage')
            ->assertHasNoErrors('language');

    $language = Language::where('code', $locale)->first();
    $component = Livewire::test(Delete::class, ['language' => $language]);

    $originalKernel = app(Kernel::class);

    Artisan::swap(
        Mockery::mock(Kernel::class, function ($mock) use ($artisanCommand) {
            $mock->shouldReceive('call')
                 ->once()
                 ->with($artisanCommand)
                 ->andThrow(new Exception('Artisan command failed.'));
        })
    );

    try {
        $component
            ->assertSet('language', $language)
            ->set('control', $component->get('confirm'))
            ->call('deleteLanguage')
            ->assertHasNoErrors('control')
            ->assertHasErrors(['deleteLanguageError'])
            ->assertDispatched('languages_sorted_fail');
    } finally {
        Artisan::swap($originalKernel);
    }

    Language::where('code', $locale)->delete();
    \Illuminate\Support\Facades\Artisan::call('lang:rm ' . strtolower($locale) . ' --force');
});

it('catch `ERRORS` on `deleteLanguage` for `Language::reorderLanguages()`', function () {
    Livewire::test(Create::class)
            ->set('language', 'it')
            ->assertSet('language', 'it')
            ->call('addNewLanguage')
            ->assertHasNoErrors('language')
            ->assertDispatched('refreshLanguages')
            ->assertDispatched('language_added')
            ->assertSet('language', '');

    $this->mock(Language::class, function ($mock) {
        $mock->shouldReceive('reorderLanguages')
             ->once()
             ->andThrow(new Exception('Error reordering languages.'));
    });
    $language = Language::where('code', 'it')->first();
    $component = Livewire::test(Delete::class, ['language' => $language]);
    $component
        ->assertSet('language', $language)
        ->set('control', $component->get('confirm'))
        ->call('deleteLanguage')
        ->assertHasNoErrors('control')
        ->assertHasErrors(['deleteLanguageError'])
        ->assertDispatched('languages_sorted_fail');

    Language::where('code', 'it')->delete();
    \Illuminate\Support\Facades\Artisan::call('lang:rm it --force');
});

it('catch `ERRORS` on `deleteLanguage` for `Translation::syncToDatabase()`', function () {
    Livewire::test(Create::class)
            ->set('language', 'it')
            ->assertSet('language', 'it')
            ->call('addNewLanguage')
            ->assertHasNoErrors('language')
            ->assertDispatched('refreshLanguages')
            ->assertDispatched('language_added')
            ->assertSet('language', '');

    $this->mock(Translation::class, function ($mock) {
        $mock->shouldReceive('syncToDatabase')
             ->once()
             ->andThrow(new Exception('Error syncing translations to database.'));
    });
    $language = Language::where('code', 'it')->first();
    $component = Livewire::test(Delete::class, ['language' => $language]);
    $component
        ->assertSet('language', $language)
        ->set('control', $component->get('confirm'))
        ->call('deleteLanguage')
        ->assertHasNoErrors('control')
        ->assertHasErrors(['deleteLanguageError'])
        ->assertDispatched('languages_sorted_fail');

    Language::where('code', 'it')->delete();
    \Illuminate\Support\Facades\Artisan::call('lang:rm it --force');
});
