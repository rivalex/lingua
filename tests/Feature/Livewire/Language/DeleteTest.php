<?php

declare(strict_types=1);

use Livewire\Livewire;
use Rivalex\Lingua\Livewire\Language\Create;
use Rivalex\Lingua\Livewire\Language\Delete;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

it('can `delete language`', function () {
    expect(Language::where('code', 'it')->exists())->toBeFalse();

    Livewire::test(Create::class)
        ->set('language', 'it')
        ->call('addNewLanguage')
        ->assertHasNoErrors('language')
        ->assertDispatched('language_added');

    expect(Language::where('code', 'it')->exists())->toBeTrue();

    $language = Language::where('code', 'it')->first();
    $component = Livewire::test(Delete::class, ['language' => $language]);
    $component
        ->assertSet('language', $language)
        ->set('control', $component->get('confirm'))
        ->call('deleteLanguage')
        ->assertHasNoErrors('control')
        ->assertDispatched('refreshLanguages');

    expect(Language::where('code', 'it')->exists())->toBeFalse()
        ->and(Translation::whereNotNull('text->it')->count())->toBe(0);
});

it('catch `Validation ERRORS` on `deleteLanguage`', function () {
    Livewire::test(Create::class)
        ->set('language', 'it')
        ->call('addNewLanguage')
        ->assertHasNoErrors('language')
        ->assertDispatched('language_added');

    $language = Language::where('code', 'it')->first();
    Livewire::test(Delete::class, ['language' => $language])
        ->assertSet('language', $language)
        ->set('control', '')
        ->call('deleteLanguage')
        ->assertHasErrors(['control']);

    Language::where('code', 'it')->delete();
});

it('catch `ERRORS` on `deleteLanguage` for `Language::reorderLanguages()`', function () {
    Livewire::test(Create::class)
        ->set('language', 'it')
        ->call('addNewLanguage')
        ->assertHasNoErrors('language')
        ->assertDispatched('language_added');

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
});

it('catch `ERRORS` on `deleteLanguage` for `Translation::syncToDatabase()`', function () {
    Livewire::test(Create::class)
        ->set('language', 'it')
        ->call('addNewLanguage')
        ->assertHasNoErrors('language')
        ->assertDispatched('language_added');

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
});
