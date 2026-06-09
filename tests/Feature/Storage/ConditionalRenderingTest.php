<?php

declare(strict_types=1);

use Livewire\Livewire;
use Rivalex\Lingua\Livewire\Translation\Create;
use Rivalex\Lingua\Livewire\Translation\Row;
use Rivalex\Lingua\Models\Translation;

// §8.8 — Conditional rendering: file-mode hides type select / badge / rich editor.

// ── Create component ─────────────────────────────────────────────────────────

it('Create in DB-mode has fileMode=false and populates translationsTypes', function (): void {
    config(['lingua.storage.driver' => 'database']);

    Livewire::test(Create::class)
        ->assertSet('fileMode', false)
        ->assertNotSet('translationsTypes', []);
});

it('Create in file-mode has fileMode=true and empty translationsTypes', function (): void {
    config(['lingua.storage.driver' => 'file']);

    Livewire::test(Create::class)
        ->assertSet('fileMode', true)
        ->assertSet('translationsTypes', [])
        ->assertSet('translationType', 'text');
});

it('Create in DB-mode renders rich editors (lingua-editor present)', function (): void {
    config(['lingua.storage.driver' => 'database']);

    Livewire::test(Create::class)
        ->assertSeeHtml('lingua-editor');
});

it('Create in file-mode renders plain textarea without lingua-editor', function (): void {
    config(['lingua.storage.driver' => 'file']);

    Livewire::test(Create::class)
        ->assertDontSeeHtml('lingua-editor')
        ->assertSeeHtml('textarea');
});

// ── Row component ─────────────────────────────────────────────────────────────

it('Row in DB-mode has fileMode=false and shows lingua-editor', function (): void {
    config(['lingua.storage.driver' => 'database']);

    $translation = Translation::create([
        'group' => 'row_cr', 'key' => 'db_mode_'.uniqid(),
        'type' => 'text', 'text' => ['en' => 'Hello'],
        'is_vendor' => false, 'vendor' => null,
    ]);
    $identity = $translation->group.'|'.$translation->key.'|0|';

    Livewire::test(Row::class, ['translationIdentity' => $identity, 'currentLocale' => 'en'])
        ->assertSet('fileMode', false)
        ->assertSeeHtml('lingua-editor');

    $translation->delete();
});

it('Row in file-mode has fileMode=true, forces text type, no lingua-editor', function (): void {
    config(['lingua.storage.driver' => 'file']);

    $translation = Translation::create([
        'group' => 'row_cr', 'key' => 'file_mode_'.uniqid(),
        'type' => 'html', 'text' => ['en' => '<p>Hello</p>'],
        'is_vendor' => false, 'vendor' => null,
    ]);
    $identity = $translation->group.'|'.$translation->key.'|0|';

    Livewire::test(Row::class, ['translationIdentity' => $identity, 'currentLocale' => 'en'])
        ->assertSet('fileMode', true)
        ->assertSet('translationType', 'text')
        ->assertDontSeeHtml('lingua-editor')
        ->assertSeeHtml('textarea');

    $translation->delete();
});

it('Row in file-mode does not render type badge icon', function (): void {
    config(['lingua.storage.driver' => 'file']);

    $translation = Translation::create([
        'group' => 'row_cr', 'key' => 'badge_'.uniqid(),
        'type' => 'text', 'text' => ['en' => 'Hello'],
        'is_vendor' => false, 'vendor' => null,
    ]);
    $identity = $translation->group.'|'.$translation->key.'|0|';

    // iconColor(6) wraps SVG in h-6 w-6 div — absent in file-mode
    Livewire::test(Row::class, ['translationIdentity' => $identity, 'currentLocale' => 'en'])
        ->assertDontSeeHtml('h-6 w-6');

    $translation->delete();
});

it('Row in DB-mode renders type badge with h-6 w-6 icon', function (): void {
    config(['lingua.storage.driver' => 'database']);

    $translation = Translation::create([
        'group' => 'row_cr', 'key' => 'badge_db_'.uniqid(),
        'type' => 'text', 'text' => ['en' => 'Hello'],
        'is_vendor' => false, 'vendor' => null,
    ]);
    $identity = $translation->group.'|'.$translation->key.'|0|';

    Livewire::test(Row::class, ['translationIdentity' => $identity, 'currentLocale' => 'en'])
        ->assertSeeHtml('h-6 w-6');

    $translation->delete();
});
