<?php

declare(strict_types=1);

use Livewire\Livewire;
use Rivalex\Lingua\Livewire\Language\Create;
use Rivalex\Lingua\Livewire\Language\Delete;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

it('can `delete language`', function () {
    // Use 'af' (Afrikaans) — not in the bundled dataset, so not pre-seeded.
    expect(Language::where('code', 'af')->exists())->toBeFalse();

    Livewire::test(Create::class)
        ->set('language', 'af')
        ->call('addNewLanguage')
        ->assertHasNoErrors('language')
        ->assertDispatched('language_added');

    expect(Language::where('code', 'af')->exists())->toBeTrue();

    $language = Language::where('code', 'af')->first();
    $component = Livewire::test(Delete::class, ['language' => $language]);
    $component
        ->assertSet('language', $language)
        ->set('control', $component->get('confirm'))
        ->call('deleteLanguage')
        ->assertHasNoErrors('control')
        ->assertDispatched('refreshLanguages');

    expect(Language::where('code', 'af')->exists())->toBeFalse()
        ->and(Translation::whereNotNull('text->af')->count())->toBe(0);
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

it('does not resurrect the language from lang files after deletion', function () {
    // Regression: the old implementation ran syncToDatabase() after deleting,
    // re-importing the locale from lang/{locale} files and recreating its
    // Language record — silently undoing the removal.
    $syncDir = sys_get_temp_dir().'/lingua_lwdelete_'.str_replace('.', '_', uniqid('', true));
    mkdir($syncDir.'/en', 0777, true);
    mkdir($syncDir.'/it', 0777, true);
    file_put_contents($syncDir.'/en/ui.php', '<?php return ["hello" => "Hello"];');
    file_put_contents($syncDir.'/it/ui.php', '<?php return ["hello" => "Ciao"];');
    config(['lingua.lang_dir' => $syncDir]);

    Translation::syncToDatabase();
    expect(Language::where('code', 'it')->exists())->toBeTrue();

    $language = Language::where('code', 'it')->first();
    $component = Livewire::test(Delete::class, ['language' => $language]);
    $component
        ->set('control', $component->get('confirm'))
        ->call('deleteLanguage')
        ->assertHasNoErrors('control')
        ->assertDispatched('refreshLanguages');

    expect(Language::where('code', 'it')->exists())->toBeFalse()
        ->and(Translation::whereNotNull('text->it')->count())->toBe(0);

    // Cleanup
    foreach ([$syncDir.'/en/ui.php', $syncDir.'/it/ui.php'] as $file) {
        unlink($file);
    }
    rmdir($syncDir.'/en');
    rmdir($syncDir.'/it');
    rmdir($syncDir);
});
