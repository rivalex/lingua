<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Livewire\Livewire;
use Rivalex\Lingua\Livewire\Translation\Delete;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

it('builds an all-uppercase confirmation phrase for a non-ASCII language name', function (): void {
    Language::factory()->create(['code' => 'ru', 'name' => 'русский', 'is_default' => false]);
    $translation = Translation::create([
        'group' => 'test',
        'key' => 'confirm_case_'.uniqid(),
        'type' => 'text',
        'text' => ['en' => 'English value', 'ru' => 'Russian value'],
        'is_vendor' => false,
        'vendor' => null,
    ]);

    $component = Livewire::test(Delete::class, [
        'translationIdentity' => $translation->group.'|'.$translation->key.'|0|',
        'currentLocale' => 'ru',
    ]);

    $confirm = $component->get('confirm');

    expect($confirm)->toBe(Str::upper($confirm))
        ->and($confirm)->toContain('РУССКИЙ');

    $component
        ->set('control', $confirm)
        ->call('deleteTranslation')
        ->assertHasNoErrors()
        ->assertDispatched('translation_locale_deleted');

    expect(isset($translation->fresh()->text['ru']))->toBeFalse()
        ->and($translation->fresh()->text['en'])->toBe('English value');
});
