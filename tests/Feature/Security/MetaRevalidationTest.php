<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Rivalex\Lingua\Contracts\BaseTranslationSource;
use Rivalex\Lingua\Database\DatabaseRepository;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Exceptions\UnsafeTranslationValue;
use Rivalex\Lingua\Livewire\Translation\Update;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

/**
 * Insert a row through the query builder, bypassing the value policy (a row stored by an earlier version).
 */
function insertLegacyRow(string $group, string $key, string $type, array $text): int
{
    return (int) DB::table('language_lines')->insertGetId([
        'group' => $group,
        'key' => $key,
        'group_key' => $group.'.'.$key,
        'type' => $type,
        'text' => json_encode($text),
        'is_vendor' => false,
        'vendor' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('rejects renaming a row into an attribute key when another locale holds a quote', function (): void {
    $id = insertLegacyRow('app', 'legacy_prev', 'text', ['en' => 'Previous', 'it' => 'a" b']);
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));
    $line = $repo->toLine(Translation::findOrFail($id));

    expect(fn () => $repo->updateMeta($line, 'pagination', 'previous', LinguaType::text))
        ->toThrow(UnsafeTranslationValue::class, '[Lingua] The it value');

    $row = Translation::findOrFail($id);

    expect($row->group)->toBe('app')
        ->and($row->key)->toBe('legacy_prev')
        ->and($row->text)->toBe(['en' => 'Previous', 'it' => 'a" b']);
});

it('rejects switching a text row with a tag to markdown', function (): void {
    $id = insertLegacyRow('app', 'legacy_div', 'text', ['en' => 'Fine', 'it' => '<div>']);
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));
    $line = $repo->toLine(Translation::findOrFail($id));

    expect(fn () => $repo->updateMeta($line, 'app', 'legacy_div', LinguaType::markdown))
        ->toThrow(UnsafeTranslationValue::class, '[Lingua] The it value');

    expect(Translation::findOrFail($id)->type)->toBe(LinguaType::text);
});

it('accepts a meta change when every locale value passes the policy', function (): void {
    $id = insertLegacyRow('app', 'legacy_ok', 'text', ['en' => 'Fine', 'it' => 'Bene']);
    $repo = new DatabaseRepository(app(BaseTranslationSource::class));
    $line = $repo->toLine(Translation::findOrFail($id));

    $updated = $repo->updateMeta($line, 'app', 'legacy_ok', LinguaType::markdown);

    expect($updated->type)->toBe(LinguaType::markdown);
});

it('rolls the meta change back through Translation Update when the value is rejected', function (): void {
    Language::factory()->create(['code' => 'it', 'is_default' => false]);
    Translation::factory()->core()->create(['group' => 'other', 'key' => 'anchor', 'type' => 'text', 'text' => ['en' => 'x']]);
    $id = insertLegacyRow('app', 'legacy_roll', 'text', ['en' => 'Original', 'it' => 'Originale']);

    // The value write fails after the meta change was saved: both must be undone.
    Translation::saving(function (Translation $model): void {
        if ($model->isDirty('text')) {
            throw new UnsafeTranslationValue('[Lingua] The en value is rejected.');
        }
    });

    Livewire::test(Update::class, ['translationIdentity' => 'app|legacy_roll|0|', 'currentLocale' => 'en'])
        ->set('group', 'other')
        ->set('key', 'moved')
        ->set('textValue', 'Changed')
        ->call('updateTranslation')
        ->assertDispatched('translation_update_fail');

    $row = Translation::findOrFail($id);

    expect($row->group)->toBe('app')
        ->and($row->key)->toBe('legacy_roll')
        ->and($row->text)->toBe(['en' => 'Original', 'it' => 'Originale']);
});
