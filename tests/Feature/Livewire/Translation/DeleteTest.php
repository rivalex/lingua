<?php

use Livewire\Livewire;
use Rivalex\Lingua\Livewire\Translation\Delete;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

function makeTranslationWithLocale(): Translation
{
    return Translation::create([
        'group'     => 'test',
        'key'       => 'delete_test_'.uniqid(),
        'type'      => 'text',
        'text'      => ['en' => 'English value', 'it' => 'Italian value'],
        'is_vendor' => false,
        'vendor'    => null,
    ]);
}

it('can render `DELETE translation` component for default locale', function () {
    $translation = makeTranslationWithLocale();

    Livewire::test(Delete::class, [
        'translation'   => $translation,
        'currentLocale' => linguaDefaultLocale(),
    ])
        ->assertStatus(200)
        ->assertSet('isDefaultLocale', true);

    $translation->delete();
});

it('can render `DELETE translation` component for non-default locale', function () {
    Language::factory()->create(['code' => 'it', 'is_default' => false]);
    $translation = makeTranslationWithLocale();

    Livewire::test(Delete::class, [
        'translation'   => $translation,
        'currentLocale' => 'it',
    ])
        ->assertStatus(200)
        ->assertSet('isDefaultLocale', false);

    $translation->delete();
    Language::where('code', 'it')->delete();
});

it('shows correct `deleteHeader` for default locale (full delete)', function () {
    $translation = makeTranslationWithLocale();

    Livewire::test(Delete::class, [
        'translation'   => $translation,
        'currentLocale' => linguaDefaultLocale(),
    ])->assertSee(__('lingua::lingua.translations.delete.header'));

    $translation->delete();
});

it('shows correct `deleteHeader` for non-default locale (locale-only delete)', function () {
    Language::factory()->create(['code' => 'it', 'is_default' => false]);
    $translation = makeTranslationWithLocale();
    $italianName = Language::where('code', 'it')->first()->name;

    Livewire::test(Delete::class, [
        'translation'   => $translation,
        'currentLocale' => 'it',
    ])->assertSee(strtoupper($italianName));

    $translation->delete();
    Language::where('code', 'it')->delete();
});

it('`deleteTranslation` deletes entire translation for default locale', function () {
    $translation = makeTranslationWithLocale();
    $id = $translation->id;

    expect(Translation::find($id))->not->toBeNull();

    Livewire::test(Delete::class, [
        'translation'   => $translation,
        'currentLocale' => linguaDefaultLocale(),
    ])
        ->call('deleteTranslation')
        ->assertDispatched('translation_deleted')
        ->assertDispatched('refreshTranslationsTableDefaults');

    expect(Translation::find($id))->toBeNull();
});

it('`deleteTranslation` forgets locale translation for non-default locale', function () {
    Language::factory()->create(['code' => 'it', 'is_default' => false]);
    $translation = makeTranslationWithLocale();

    expect($translation->text['it'])->toBe('Italian value');

    Livewire::test(Delete::class, [
        'translation'   => $translation,
        'currentLocale' => 'it',
    ])
        ->call('deleteTranslation')
        ->assertDispatched('translation_locale_deleted')
        ->assertDispatched('refreshTranslationRow.'.$translation->id);

    $translation->refresh();
    expect(isset($translation->text['it']))->toBeFalse();
    expect($translation->text['en'])->toBe('English value');

    $translation->delete();
    Language::where('code', 'it')->delete();
});

it('translation is gone from DB after global delete', function () {
    $translation = makeTranslationWithLocale();
    $id = $translation->id;

    Livewire::test(Delete::class, [
        'translation'   => $translation,
        'currentLocale' => linguaDefaultLocale(),
    ])->call('deleteTranslation');

    expect(Translation::find($id))->toBeNull();
});

it('translation EN value still exists after locale-only delete of IT', function () {
    Language::factory()->create(['code' => 'it', 'is_default' => false]);
    $translation = makeTranslationWithLocale();
    $id = $translation->id;

    Livewire::test(Delete::class, [
        'translation'   => $translation,
        'currentLocale' => 'it',
    ])->call('deleteTranslation');

    $fresh = Translation::find($id);
    expect($fresh)->not->toBeNull();
    expect(isset($fresh->text['it']))->toBeFalse();
    expect($fresh->text['en'])->toBe('English value');

    $fresh->delete();
    Language::where('code', 'it')->delete();
});
