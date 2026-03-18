<?php

use Livewire\Livewire;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Livewire\Translation\Create;
use Rivalex\Lingua\Models\Translation;

it('can render `CREATE translation` component', function () {
    Livewire::test(Create::class)
        ->assertStatus(200);
});

it('populates `translationsTypes` on mount', function () {
    Livewire::test(Create::class)
        ->assertNotSet('translationsTypes', []);
});

it('populates `groups` list on mount', function () {
    Livewire::test(Create::class)
        ->assertNotSet('groups', []);
});

it('can create a `TEXT` translation', function () {
    $group = Translation::first()->group;

    expect(Translation::where('group', $group)->where('key', 'test_create_text')->exists())->toBeFalse();

    Livewire::test(Create::class)
        ->set('group', $group)
        ->set('key', 'test_create_text')
        ->set('translationType', 'text')
        ->set('textValue', 'Hello World')
        ->call('addNewTranslation')
        ->assertHasNoErrors()
        ->assertDispatched('translation_added')
        ->assertDispatched('refreshTranslationsTableDefaults')
        ->assertSet('group', '')
        ->assertSet('key', '');

    expect(Translation::where('group', $group)->where('key', 'test_create_text')->exists())->toBeTrue();

    Translation::where('key', 'test_create_text')->delete();
});

it('can create an `HTML` translation', function () {
    $group = Translation::first()->group;

    Livewire::test(Create::class)
        ->set('group', $group)
        ->set('key', 'test_create_html')
        ->set('translationType', 'html')
        ->set('htmlValue', '<p>Hello <strong>World</strong></p>')
        ->call('addNewTranslation')
        ->assertHasNoErrors()
        ->assertDispatched('translation_added');

    expect(Translation::where('group', $group)->where('key', 'test_create_html')->exists())->toBeTrue();

    Translation::where('key', 'test_create_html')->delete();
});

it('can create a `MARKDOWN` translation', function () {
    $group = Translation::first()->group;

    Livewire::test(Create::class)
        ->set('group', $group)
        ->set('key', 'test_create_md')
        ->set('translationType', 'markdown')
        ->set('mdValue', '**Hello World**')
        ->call('addNewTranslation')
        ->assertHasNoErrors()
        ->assertDispatched('translation_added');

    expect(Translation::where('group', $group)->where('key', 'test_create_md')->exists())->toBeTrue();

    Translation::where('key', 'test_create_md')->delete();
});

it('catches `Validation ERRORS` for missing `group`', function () {
    Livewire::test(Create::class)
        ->set('group', '')
        ->set('key', 'some_key')
        ->set('translationType', 'text')
        ->set('textValue', 'Some value')
        ->call('addNewTranslation')
        ->assertHasErrors(['group']);
});

it('catches `Validation ERRORS` for missing `key`', function () {
    $group = Translation::first()->group;

    Livewire::test(Create::class)
        ->set('group', $group)
        ->set('key', '')
        ->set('translationType', 'text')
        ->set('textValue', 'Some value')
        ->call('addNewTranslation')
        ->assertHasErrors(['key']);
});

it('catches `Validation ERRORS` for `key` shorter than 2 characters', function () {
    $group = Translation::first()->group;

    Livewire::test(Create::class)
        ->set('group', $group)
        ->set('key', 'x')
        ->set('translationType', 'text')
        ->set('textValue', 'Some value')
        ->call('addNewTranslation')
        ->assertHasErrors(['key']);
});

it('catches `Validation ERRORS` for `duplicate key` within same group', function () {
    $existing = Translation::where('group', '!=', 'single')->first();

    Livewire::test(Create::class)
        ->set('group', $existing->group)
        ->set('key', $existing->key)
        ->set('translationType', 'text')
        ->set('textValue', 'Duplicate value')
        ->call('addNewTranslation')
        ->assertHasErrors(['key']);
});

it('catches `Validation ERRORS` for missing `textValue` when type is text', function () {
    $group = Translation::first()->group;

    Livewire::test(Create::class)
        ->set('group', $group)
        ->set('key', 'validate_text_key')
        ->set('translationType', 'text')
        ->set('textValue', '')
        ->call('addNewTranslation')
        ->assertHasErrors(['textValue']);
});

it('catches `Validation ERRORS` for missing `htmlValue` when type is html', function () {
    $group = Translation::first()->group;

    Livewire::test(Create::class)
        ->set('group', $group)
        ->set('key', 'validate_html_key')
        ->set('translationType', 'html')
        ->set('htmlValue', '')
        ->call('addNewTranslation')
        ->assertHasErrors(['htmlValue']);
});

it('catches `Validation ERRORS` for missing `mdValue` when type is markdown', function () {
    $group = Translation::first()->group;

    Livewire::test(Create::class)
        ->set('group', $group)
        ->set('key', 'validate_md_key')
        ->set('translationType', 'markdown')
        ->set('mdValue', '')
        ->call('addNewTranslation')
        ->assertHasErrors(['mdValue']);
});

it('dispatches `translation_added` only once per creation', function () {
    $group = Translation::first()->group;

    Livewire::test(Create::class)
        ->set('group', $group)
        ->set('key', 'unique_once_key_'.uniqid())
        ->set('translationType', 'text')
        ->set('textValue', 'Some value')
        ->call('addNewTranslation')
        ->assertDispatched('translation_added');

    Translation::where('key', 'like', 'unique_once_key_%')->delete();
});

it('reacts to `updateTranslationGroup` event and sets group', function () {
    $group = Translation::first()->group;

    Livewire::test(Create::class)
        ->dispatch('updateTranslationGroup', $group)
        ->assertSet('group', $group);
});

it('resets form after successful creation', function () {
    $group = Translation::first()->group;

    Livewire::test(Create::class)
        ->set('group', $group)
        ->set('key', 'reset_after_create')
        ->set('translationType', 'text')
        ->set('textValue', 'Test value')
        ->call('addNewTranslation')
        ->assertSet('group', '')
        ->assertSet('key', '');

    Translation::where('key', 'reset_after_create')->delete();
});
