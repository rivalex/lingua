<?php

declare(strict_types=1);

use Livewire\Livewire;
use Rivalex\Lingua\Lingua;
use Rivalex\Lingua\Livewire\Translation\Delete;
use Rivalex\Lingua\Livewire\Translation\Update;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

function guardsEnsureLanguage(string $code): void
{
    if (! Language::where('code', $code)->exists()) {
        Language::factory()->create(['code' => $code, 'is_default' => false]);
    }
}

it('keeps the default flag when the default language is added again', function (): void {
    $default = linguaDefaultLocale();
    Language::query()->update(['is_default' => false]);
    Language::updateOrCreate(['code' => $default], ['is_default' => true]);

    Lingua::addLanguage($default);

    expect(Language::where('code', $default)->first()->is_default)->toBeTrue();
});

it('refuses a delete whose default-locale wording went stale and changes nothing', function (): void {
    guardsEnsureLanguage('it');
    $translation = Translation::create([
        'group' => 'test', 'key' => 'guard_stale_key', 'type' => 'text',
        'text' => ['en' => 'Hello', 'it' => 'Ciao'], 'is_vendor' => false, 'vendor' => null,
    ]);

    $component = Livewire::test(Delete::class, [
        'translationIdentity' => 'test|guard_stale_key|0|',
        'currentLocale' => 'en',
    ]);

    // The default language changes after the modal was mounted.
    app()->setFallbackLocale('it');

    $component
        ->set('control', 'DELETE TRANSLATION')
        ->call('deleteTranslation')
        ->assertHasErrors(['deleteTranslationError'])
        ->assertSee(__('lingua::lingua.translations.delete.stale'));

    expect($translation->fresh()->text)->toBe(['en' => 'Hello', 'it' => 'Ciao']);
});

it('ignores a tampered group and key sent from a non-default locale', function (): void {
    guardsEnsureLanguage('it');
    $translation = Translation::create([
        'group' => 'test', 'key' => 'guard_locked_key', 'type' => 'text',
        'text' => ['en' => 'Hello'], 'is_vendor' => false, 'vendor' => null,
    ]);

    Livewire::test(Update::class, [
        'translationIdentity' => 'test|guard_locked_key|0|',
        'currentLocale' => 'it',
    ])
        ->set('group', 'hacked')
        ->set('key', 'hacked_key')
        ->set('textValue', 'Ciao')
        ->call('updateTranslation')
        ->assertHasNoErrors();

    $fresh = $translation->fresh();

    expect($fresh->group)->toBe('test')
        ->and($fresh->key)->toBe('guard_locked_key')
        ->and($fresh->text['it'])->toBe('Ciao');
});
