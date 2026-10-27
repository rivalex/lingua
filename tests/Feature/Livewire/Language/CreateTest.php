<?php

declare(strict_types=1);

use Livewire\Livewire;
use Rivalex\Lingua\Contracts\TranslationRepository;
use Rivalex\Lingua\Facades\Lingua;
use Rivalex\Lingua\Livewire\Language\Create;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

it('can render `CREATE` language component', function () {
    Livewire::test(Create::class)
        ->assertStatus(200)
        ->assertSeeHtml('Add new Language');
});

it('can initialize `availableLanguages` properties', function () {
    $availableLanguages = count(Lingua::notInstalled());
    Livewire::test(Create::class)
        ->set('availableLanguages', [])
        ->assertCount('availableLanguages', 0)
        ->call('refreshLanguages')
        ->assertCount('availableLanguages', $availableLanguages);
});

it('can add new language with `addNewLanguage` method', function () {
    // Point at the real shipped bundle so the seeding path is exercised.
    config(['lingua.base_translations_path' => dirname(__DIR__, 4).'/resources/translations']);

    expect(Language::where('code', 'it')->exists())->toBeFalse()
        ->and(Translation::whereNotNull('text->it')->count())->toBe(0);

    Livewire::test(Create::class)
        ->set('language', 'it')
        ->assertSet('language', 'it')
        ->call('addNewLanguage')
        ->assertHasNoErrors('language')
        ->assertDispatched('refreshLanguages')
        ->assertDispatched('language_added')
        ->assertSet('language', '');

    expect(Language::where('code', 'it')->exists())->toBeTrue();

    // A known bundled value must appear in language_lines.text['it'].
    $row = Translation::where('group', 'validation')->where('key', 'required')->first();
    expect($row)->not->toBeNull()
        ->and($row->text['it'] ?? null)->not->toBeNull()->not->toBe('',
            'installLocale(it) should seed validation.required[it] from the bundled dataset'
        );

    Language::where('code', 'it')->delete();
});

it('catch `Validation ERRORS` on `addNewLanguage`', function () {
    $locale = '';
    Livewire::test(Create::class)
        ->set('language', $locale)
        ->assertSet('language', $locale)
        ->call('addNewLanguage')
        ->assertHasErrors(['language']);
});

it('catch `ERRORS` on `addNewLanguage` for unknown locale', function () {
    $locale = 'xx';
    Livewire::test(Create::class)
        ->set('language', $locale)
        ->assertSet('language', $locale)
        ->call('addNewLanguage')
        ->assertHasNoErrors('language')
        ->assertHasErrors(['addLanguageError'])
        ->assertDispatched('language_added_fail')
        ->assertSet('language', '');
});

it('catch `ERRORS` on `addNewLanguage` when installLocale fails', function () {
    $this->mock(TranslationRepository::class, function ($mock) {
        $mock->shouldReceive('installLocale')
            ->once()
            ->andThrow(new Exception('Error syncing'));
    });

    Livewire::test(Create::class)
        ->set('language', 'it')
        ->assertSet('language', 'it')
        ->call('addNewLanguage')
        ->assertHasErrors(['addLanguageError'])
        ->assertDispatched('language_added_fail')
        ->assertSet('language', '');

    Language::where('code', 'it')->delete();
});

it('react on `refreshLanguages event` dispatched', function () {
    $availableLanguages = count(Lingua::notInstalled());
    Livewire::test(Create::class)
        ->set('availableLanguages', [])
        ->assertCount('availableLanguages', 0)
        ->dispatch('refreshLanguages')
        ->assertCount('availableLanguages', $availableLanguages);
});
