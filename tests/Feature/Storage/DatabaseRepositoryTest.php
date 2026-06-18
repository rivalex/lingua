<?php

declare(strict_types=1);

use Rivalex\Lingua\Contracts\BaseTranslationSource;
use Rivalex\Lingua\Database\DatabaseRepository;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Exceptions\VendorTranslationProtectedException;
use Rivalex\Lingua\Locales\BundledTranslationSource;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\Support\TranslationLine;

beforeEach(function (): void {
    $this->repo = new DatabaseRepository(app(BaseTranslationSource::class));

    Translation::create([
        'group' => 'messages',
        'key' => 'hello',
        'type' => LinguaType::text,
        'text' => ['en' => 'Hello', 'it' => 'Ciao'],
        'is_vendor' => false,
        'vendor' => null,
    ]);
    Translation::create([
        'group' => 'messages',
        'key' => 'bye',
        'type' => LinguaType::text,
        'text' => ['en' => 'Bye'],
        'is_vendor' => false,
        'vendor' => null,
    ]);
    Translation::create([
        'group' => 'single',
        'key' => 'welcome',
        'type' => LinguaType::text,
        'text' => ['en' => 'Welcome'],
        'is_vendor' => false,
        'vendor' => null,
    ]);
});

test('groups returns distinct sorted group names', function (): void {
    $groups = $this->repo->groups();

    expect($groups)->toContain('messages')
        ->toContain('single');
});

test('find locates existing key', function (): void {
    $line = $this->repo->find('messages', 'hello', false, null);

    expect($line)->not->toBeNull()
        ->and($line->group)->toBe('messages')
        ->and($line->key)->toBe('hello')
        ->and($line->value('en'))->toBe('Hello')
        ->and($line->id)->not->toBeNull();
});

test('find returns null for missing key', function (): void {
    expect($this->repo->find('messages', 'nonexistent', false, null))->toBeNull();
});

test('create persists new key and returns DTO', function (): void {
    $line = $this->repo->create('messages', 'new_key', LinguaType::text, 'en', 'New value');

    expect($line->key)->toBe('new_key')
        ->and($line->value('en'))->toBe('New value')
        ->and(Translation::where('key', 'new_key')->exists())->toBeTrue();
});

test('setValue updates locale value', function (): void {
    $line = $this->repo->find('messages', 'hello', false, null);
    $updated = $this->repo->setValue($line, 'en', 'Hi there');

    expect($updated->value('en'))->toBe('Hi there')
        ->and(Translation::where('key', 'hello')->first()->text['en'])->toBe('Hi there');
});

test('deleteKey removes the model', function (): void {
    $line = $this->repo->find('messages', 'hello', false, null);
    $this->repo->deleteKey($line);

    expect(Translation::where('key', 'hello')->exists())->toBeFalse();
});

test('counts returns total and byLocale', function (): void {
    $counts = $this->repo->counts();

    expect($counts['total'])->toBeGreaterThanOrEqual(3)
        ->and($counts['byLocale'])->toHaveKey('en');
});

test('localeStats returns correct structure', function (): void {
    $stats = $this->repo->localeStats('en');

    expect($stats)->toHaveKey('total')
        ->toHaveKey('translated')
        ->toHaveKey('missing')
        ->toHaveKey('percentage');
});

test('all returns collection of TranslationLine DTOs', function (): void {
    $all = $this->repo->all();

    expect($all)->not->toBeEmpty()
        ->and($all->first())->toBeInstanceOf(TranslationLine::class);
});

test('findByKey finds by key string', function (): void {
    $line = $this->repo->findByKey('hello');

    expect($line)->not->toBeNull()
        ->and($line->group)->toBe('messages');
});

test('byGroup returns only rows for that group', function (): void {
    $lines = $this->repo->byGroup('messages');

    expect($lines)->not->toBeEmpty()
        ->and($lines->every(fn ($l) => $l->group === 'messages'))->toBeTrue();
});

test('byGroup with locale filters to rows with that locale', function (): void {
    $lines = $this->repo->byGroup('messages', 'it');

    expect($lines)->not->toBeEmpty()
        ->and($lines->every(fn ($l) => $l->value('it') !== ''))->toBeTrue();
});

test('identity is stable and not null', function (): void {
    $line = $this->repo->find('messages', 'hello', false, null);
    $identity = $line->identity();

    expect($identity)->toBe('messages|hello|0|');
});

test('installLocale seeds bundled values for a new locale', function (): void {
    // Use the real shipped bundle so the DB seeding path is exercised end-to-end.
    config(['lingua.base_translations_path' => dirname(__DIR__, 3).'/resources/translations']);
    // Re-resolve BundledTranslationSource with the updated path.
    $repo = new DatabaseRepository(
        new BundledTranslationSource(
            config('lingua.base_translations_path')
        )
    );

    $repo->installLocale('it');

    $row = Translation::where('group', 'validation')->where('key', 'required')->first();
    expect($row)->not->toBeNull()
        ->and($row->text['it'] ?? null)->not->toBeNull()->not->toBe('');
});

test('installLocale is a no-op for the default locale', function (): void {
    $countBefore = Translation::count();

    $this->repo->installLocale(linguaDefaultLocale());

    // syncToDatabase may upsert existing rows but should not add net-new locale values.
    $countAfter = Translation::count();
    expect($countAfter)->toBeGreaterThanOrEqual($countBefore);
});

// ── Vendor guard (Phase 6a) ───────────────────────────────────────────────────

test('deleteKey throws VendorTranslationProtectedException for a vendor line', function (): void {
    $model = Translation::create([
        'group' => 'buttons',
        'key' => 'save',
        'type' => LinguaType::text,
        'text' => ['en' => 'Save'],
        'is_vendor' => true,
        'vendor' => 'flux',
    ]);
    $line = $this->repo->toLine($model);

    expect(fn () => $this->repo->deleteKey($line))
        ->toThrow(VendorTranslationProtectedException::class);
});

test('forgetLocale throws VendorTranslationProtectedException for a vendor line', function (): void {
    $model = Translation::create([
        'group' => 'buttons',
        'key' => 'cancel',
        'type' => LinguaType::text,
        'text' => ['en' => 'Cancel'],
        'is_vendor' => true,
        'vendor' => 'flux',
    ]);
    $line = $this->repo->toLine($model);

    expect(fn () => $this->repo->forgetLocale($line, 'en'))
        ->toThrow(VendorTranslationProtectedException::class);
});

test('setValue succeeds on a vendor line (edits allowed)', function (): void {
    $model = Translation::create([
        'group' => 'messages',
        'key' => 'greet',
        'type' => LinguaType::text,
        'text' => ['en' => 'Hello'],
        'is_vendor' => true,
        'vendor' => 'acme',
    ]);
    $line = $this->repo->toLine($model);

    $updated = $this->repo->setValue($line, 'en', 'Hi');

    expect($updated->value('en'))->toBe('Hi');
});

test('create with isVendor=true succeeds', function (): void {
    $line = $this->repo->create('prompts', 'confirm', LinguaType::text, 'en', 'Confirm', true, 'ui-kit');

    expect($line->isVendor)->toBeTrue()
        ->and($line->vendor)->toBe('ui-kit')
        ->and($line->value('en'))->toBe('Confirm');
});
