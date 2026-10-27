<?php

declare(strict_types=1);

use Livewire\Livewire;
use Rivalex\Lingua\Contracts\TranslationRepository;
use Rivalex\Lingua\Facades\Lingua;
use Rivalex\Lingua\Livewire\Language\Delete;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

/**
 * Snapshot of every translation row: id => text, to compare before and after.
 *
 * @return array<int, array<string, string>>
 */
function deleteSafetySnapshot(): array
{
    return Translation::query()->orderBy('id')->get()->mapWithKeys(fn ($row) => [$row->id => $row->text])->all();
}

it('refuses to delete the default language and keeps its values', function () {
    $default = Language::where('is_default', true)->first()
        ?? Language::factory()->create(['code' => 'en', 'is_default' => true]);
    $line = Translation::factory()->core()->create(['text' => [$default->code => 'Keep me']]);
    $before = deleteSafetySnapshot();

    $component = Livewire::test(Delete::class, ['language' => $default]);
    $component
        ->set('control', $component->get('confirm'))
        ->call('deleteLanguage')
        ->assertHasErrors(['deleteLanguageError' => __('lingua::lingua.languages.delete.default_forbidden')])
        ->assertNotDispatched('refreshLanguages')
        ->assertNotDispatched('language_delete_fail');

    expect(Language::where('code', $default->code)->exists())->toBeTrue()
        ->and(deleteSafetySnapshot())->toBe($before)
        ->and($line->fresh()->text[$default->code])->toBe('Keep me');
});

it('deletes a language that has vendor rows holding its locale', function () {
    Language::factory()->create(['code' => 'it', 'is_default' => false]);
    $core = Translation::factory()->core()->create(['text' => ['en' => 'Hello', 'it' => 'Ciao']]);
    $vendor = Translation::factory()->vendor('acme')->create(['text' => ['en' => 'Hi', 'it' => 'Salve']]);

    $language = Language::where('code', 'it')->first();
    $component = Livewire::test(Delete::class, ['language' => $language]);
    $component
        ->set('control', $component->get('confirm'))
        ->call('deleteLanguage')
        ->assertHasNoErrors()
        ->assertDispatched('refreshLanguages');

    expect(Language::where('code', 'it')->exists())->toBeFalse()
        ->and($core->fresh()->text)->not->toHaveKey('it')
        ->and($vendor->fresh()->text)->toHaveKey('it', 'Salve');
});

it('leaves every row unchanged when the deletion fails halfway in database mode', function () {
    Language::factory()->create(['code' => 'it', 'is_default' => false]);
    Translation::factory()->core()->create(['text' => ['en' => 'One', 'it' => 'Uno']]);
    Translation::factory()->core()->create(['text' => ['en' => 'Two', 'it' => 'Due']]);
    $before = deleteSafetySnapshot();

    $real = app(TranslationRepository::class);
    $calls = 0;
    $double = Mockery::mock(TranslationRepository::class);
    $double->shouldReceive('all')->andReturnUsing(fn () => $real->all());
    $double->shouldReceive('forgetLocale')->andReturnUsing(function ($line, $locale) use ($real, &$calls) {
        if (++$calls === 2) {
            throw new RuntimeException('Forced failure on the second line');
        }
        $real->forgetLocale($line, $locale);
    });
    app()->bind(TranslationRepository::class, fn () => $double);

    $language = Language::where('code', 'it')->first();
    $component = Livewire::test(Delete::class, ['language' => $language]);
    $component
        ->set('control', $component->get('confirm'))
        ->call('deleteLanguage')
        ->assertHasErrors(['deleteLanguageError'])
        ->assertDispatched('language_delete_fail');

    expect($calls)->toBe(2)
        ->and(deleteSafetySnapshot())->toBe($before)
        ->and(Language::where('code', 'it')->exists())->toBeTrue();
});

it('throws when removing the default language through Lingua', function () {
    $default = Language::where('is_default', true)->first()
        ?? Language::factory()->create(['code' => 'en', 'is_default' => true]);

    expect(fn () => Lingua::removeLanguage($default->code))
        ->toThrow(InvalidArgumentException::class, '[Lingua] The default language cannot be removed')
        ->and(Language::where('code', $default->code)->exists())->toBeTrue();
});
