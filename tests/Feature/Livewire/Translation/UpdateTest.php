<?php

use Livewire\Livewire;
use Rivalex\Lingua\Livewire\Translation\Update;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

function makeTextTranslation(): Translation
{
    return Translation::create([
        'group'     => 'test',
        'key'       => 'update_test_'.uniqid(),
        'type'      => 'text',
        'text'      => ['en' => 'Original text'],
        'is_vendor' => false,
        'vendor'    => null,
    ]);
}

it('can render `UPDATE translation` component', function () {
    $translation = makeTextTranslation();

    Livewire::test(Update::class, [
        'translation'   => $translation,
        'currentLocale' => 'en',
    ])->assertStatus(200);

    $translation->delete();
});

it('populates defaults from `translation` on mount', function () {
    $translation = makeTextTranslation();

    Livewire::test(Update::class, [
        'translation'   => $translation,
        'currentLocale' => 'en',
    ])
        ->assertSet('group', $translation->group)
        ->assertSet('key', $translation->key)
        ->assertSet('textValue', 'Original text')
        ->assertSet('translationType', 'text');

    $translation->delete();
});

it('sets `required` to true for default locale', function () {
    $translation = makeTextTranslation();

    Livewire::test(Update::class, [
        'translation'   => $translation,
        'currentLocale' => linguaDefaultLocale(),
    ])->assertSet('required', true)
      ->assertSet('locked', false);

    $translation->delete();
});

it('sets `locked` to true for non-default locale', function () {
    Language::factory()->create(['code' => 'it', 'is_default' => false]);
    $translation = makeTextTranslation();

    Livewire::test(Update::class, [
        'translation'   => $translation,
        'currentLocale' => 'it',
    ])->assertSet('locked', true)
      ->assertSet('required', false);

    $translation->delete();
    Language::where('code', 'it')->delete();
});

it('can `UPDATE` a text translation value', function () {
    $translation = makeTextTranslation();

    Livewire::test(Update::class, [
        'translation'   => $translation,
        'currentLocale' => 'en',
    ])
        ->set('textValue', 'Updated text value')
        ->call('updateTranslation')
        ->assertHasNoErrors()
        ->assertDispatched('translation_updated')
        ->assertDispatched('refreshTranslationRow.'.$translation->id);

    $translation->refresh();
    expect($translation->text['en'])->toBe('Updated text value');

    $translation->delete();
});

it('can `UPDATE` an HTML translation value', function () {
    $translation = Translation::create([
        'group'     => 'test',
        'key'       => 'update_html_'.uniqid(),
        'type'      => 'html',
        'text'      => ['en' => '<p>Original</p>'],
        'is_vendor' => false,
        'vendor'    => null,
    ]);

    Livewire::test(Update::class, [
        'translation'   => $translation,
        'currentLocale' => 'en',
    ])
        ->set('translationType', 'html')
        ->set('htmlValue', '<p>Updated HTML</p>')
        ->call('updateTranslation')
        ->assertHasNoErrors()
        ->assertDispatched('translation_updated');

    $translation->refresh();
    expect($translation->text['en'])->toBe('<p>Updated HTML</p>');

    $translation->delete();
});

it('can `UPDATE` a Markdown translation value', function () {
    $translation = Translation::create([
        'group'     => 'test',
        'key'       => 'update_md_'.uniqid(),
        'type'      => 'markdown',
        'text'      => ['en' => '**Original**'],
        'is_vendor' => false,
        'vendor'    => null,
    ]);

    Livewire::test(Update::class, [
        'translation'   => $translation,
        'currentLocale' => 'en',
    ])
        ->set('translationType', 'markdown')
        ->set('mdValue', '**Updated MD**')
        ->call('updateTranslation')
        ->assertHasNoErrors()
        ->assertDispatched('translation_updated');

    $translation->refresh();
    expect($translation->text['en'])->toBe('**Updated MD**');

    $translation->delete();
});

it('catches `Validation ERRORS` for missing `group` on update', function () {
    $translation = makeTextTranslation();

    Livewire::test(Update::class, [
        'translation'   => $translation,
        'currentLocale' => 'en',
    ])
        ->set('group', '')
        ->set('textValue', 'Some value')
        ->call('updateTranslation')
        ->assertHasErrors(['group']);

    $translation->delete();
});

it('catches `Validation ERRORS` for missing `key` on update', function () {
    $translation = makeTextTranslation();

    Livewire::test(Update::class, [
        'translation'   => $translation,
        'currentLocale' => 'en',
    ])
        ->set('key', '')
        ->set('textValue', 'Some value')
        ->call('updateTranslation')
        ->assertHasErrors(['key']);

    $translation->delete();
});

it('does not allow updating `key` to less than 2 characters', function () {
    $translation = makeTextTranslation();

    Livewire::test(Update::class, [
        'translation'   => $translation,
        'currentLocale' => 'en',
    ])
        ->set('key', 'x')
        ->set('textValue', 'Some value')
        ->call('updateTranslation')
        ->assertHasErrors(['key']);

    $translation->delete();
});

it('dispatches `translation_updated` after a successful update', function () {
    $translation = makeTextTranslation();

    Livewire::test(Update::class, [
        'translation'   => $translation,
        'currentLocale' => 'en',
    ])
        ->set('textValue', 'Dispatched successfully')
        ->call('updateTranslation')
        ->assertDispatched('translation_updated');

    $translation->delete();
});

it('responds to `updateTranslationModal.{id}` event by refreshing defaults', function () {
    $translation = makeTextTranslation();

    Livewire::test(Update::class, [
        'translation'   => $translation,
        'currentLocale' => 'en',
    ])
        ->set('group', 'changed_group')
        ->dispatch('updateTranslationModal.'.$translation->id)
        ->assertSet('group', $translation->group);

    $translation->delete();
});
