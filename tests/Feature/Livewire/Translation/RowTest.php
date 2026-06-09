<?php

use Livewire\Livewire;
use Rivalex\Lingua\Livewire\Translation\Row;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

function makeRowTranslation(): Translation
{
    return Translation::create([
        'group' => 'test',
        'key' => 'row_test_'.uniqid(),
        'type' => 'text',
        'text' => ['en' => 'Default English value'],
        'is_vendor' => false,
        'vendor' => null,
    ]);
}

it('can render `TRANSLATION ROW` component', function () {
    $translation = makeRowTranslation();
    $identity = $translation->group.'|'.$translation->key.'|0|';

    Livewire::test(Row::class, [
        'translationIdentity' => $identity,
        'currentLocale' => 'en',
    ])->assertStatus(200);

    $translation->delete();
});

it('shows the `default value` for the default locale', function () {
    $translation = makeRowTranslation();
    $identity = $translation->group.'|'.$translation->key.'|0|';

    Livewire::test(Row::class, [
        'translationIdentity' => $identity,
        'currentLocale' => 'en',
    ])
        ->assertSet('defaultValue', 'Default English value')
        ->assertSet('value', 'Default English value');

    $translation->delete();
});

it('shows empty `value` when locale translation is missing', function () {
    Language::factory()->create(['code' => 'it', 'is_default' => false]);
    $translation = makeRowTranslation();
    $identity = $translation->group.'|'.$translation->key.'|0|';

    Livewire::test(Row::class, [
        'translationIdentity' => $identity,
        'currentLocale' => 'it',
    ])
        ->assertSet('value', '')
        ->assertSet('defaultValue', 'Default English value');

    $translation->delete();
    Language::where('code', 'it')->delete();
});

it('shows `locale value` when locale translation exists', function () {
    Language::factory()->create(['code' => 'it', 'is_default' => false]);

    $translation = Translation::create([
        'group' => 'test',
        'key' => 'row_it_'.uniqid(),
        'type' => 'text',
        'text' => ['en' => 'English', 'it' => 'Italiano'],
        'is_vendor' => false,
        'vendor' => null,
    ]);
    $identity = $translation->group.'|'.$translation->key.'|0|';

    Livewire::test(Row::class, [
        'translationIdentity' => $identity,
        'currentLocale' => 'it',
    ])
        ->assertSet('value', 'Italiano')
        ->assertSet('defaultValue', 'English');

    $translation->delete();
    Language::where('code', 'it')->delete();
});

it('`updatedValue` saves translation when value is not empty', function () {
    $translation = makeRowTranslation();
    $identity = $translation->group.'|'.$translation->key.'|0|';

    Livewire::test(Row::class, [
        'translationIdentity' => $identity,
        'currentLocale' => 'en',
    ])->set('value', 'New saved value');

    $translation->refresh();
    expect($translation->text['en'])->toBe('New saved value');

    $translation->delete();
});

it('`updatedValue` dispatches `updateTranslationModal.{identity}` after saving', function () {
    $translation = makeRowTranslation();
    $identity = $translation->group.'|'.$translation->key.'|0|';

    Livewire::test(Row::class, [
        'translationIdentity' => $identity,
        'currentLocale' => 'en',
    ])
        ->set('value', 'Updated via row')
        ->assertDispatched('updateTranslationModal.'.$identity);

    $translation->delete();
});

it('`syncFromDefault` copies default value to current locale', function () {
    Language::factory()->create(['code' => 'it', 'is_default' => false]);
    $translation = makeRowTranslation();
    $identity = $translation->group.'|'.$translation->key.'|0|';

    Livewire::test(Row::class, [
        'translationIdentity' => $identity,
        'currentLocale' => 'it',
    ])
        ->call('syncFromDefault')
        ->assertSet('value', 'Default English value');

    $translation->refresh();
    expect($translation->text['it'])->toBe('Default English value');

    $translation->delete();
    Language::where('code', 'it')->delete();
});

it('`refreshTranslationRow.{identity}` event refreshes the row', function () {
    Language::factory()->create(['code' => 'it', 'is_default' => false]);

    $translation = Translation::create([
        'group' => 'test',
        'key' => 'row_refresh_'.uniqid(),
        'type' => 'text',
        'text' => ['en' => 'Old value', 'it' => 'Old IT value'],
        'is_vendor' => false,
        'vendor' => null,
    ]);
    $identity = $translation->group.'|'.$translation->key.'|0|';

    $component = Livewire::test(Row::class, [
        'translationIdentity' => $identity,
        'currentLocale' => 'it',
    ]);

    $component->assertSet('value', 'Old IT value');

    // Update translation externally
    $text = $translation->text;
    $text['it'] = 'New IT value';
    $translation->text = $text;
    $translation->save();

    $component->dispatch('refreshTranslationRow.'.$identity)
        ->assertSet('value', 'New IT value');

    $translation->delete();
    Language::where('code', 'it')->delete();
});

it('sets `editModalName` and `deleteModalName` on mount', function () {
    $translation = makeRowTranslation();
    $identity = $translation->group.'|'.$translation->key.'|0|';

    Livewire::test(Row::class, [
        'translationIdentity' => $identity,
        'currentLocale' => 'en',
    ])
        ->assertSet('editModalName', 'translation-update-modal-'.md5($identity))
        ->assertSet('deleteModalName', 'translation-delete-modal-'.md5($identity));

    $translation->delete();
});

it('validates `value` is required when current locale is default', function () {
    $translation = makeRowTranslation();
    $identity = $translation->group.'|'.$translation->key.'|0|';

    Livewire::test(Row::class, [
        'translationIdentity' => $identity,
        'currentLocale' => linguaDefaultLocale(),
    ])
        ->set('value', '')
        ->assertHasErrors(['value']);

    $translation->delete();
});
