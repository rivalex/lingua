<?php

declare(strict_types=1);

use Rivalex\Lingua\Contracts\BaseTranslationSource;
use Rivalex\Lingua\Database\DatabaseRepository;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Locales\BundledTranslationSource;
use Rivalex\Lingua\Models\Translation;

beforeEach(function (): void {
    $this->repo = new DatabaseRepository(app(BaseTranslationSource::class));

    // Group isolated from the seeder so assertions stay deterministic.
    Translation::create([
        'group' => 'sec',
        'key' => 'present',
        'type' => LinguaType::text,
        'text' => ['en' => 'Present', 'de' => 'Vorhanden'],
        'is_vendor' => false,
        'vendor' => null,
    ]);
    Translation::create([
        'group' => 'sec',
        'key' => 'absent',
        'type' => LinguaType::text,
        'text' => ['en' => 'Absent'],
        'is_vendor' => false,
        'vendor' => null,
    ]);
    Translation::create([
        'group' => 'sec',
        'key' => 'empty',
        'type' => LinguaType::text,
        'text' => ['en' => 'Empty', 'de' => ''],
        'is_vendor' => false,
        'vendor' => null,
    ]);
});

// ── F2: no JSON-SQL arrow operators ───────────────────────────────────────────

test('F2-a paginate only-missing filters in PHP without JSON-SQL', function (): void {
    $missing = $this->repo->paginate('de', '', 'sec', true, 50);

    $keys = collect($missing->items())->map(fn ($l) => $l->key)->all();

    // 'present' has a non-empty 'de'; 'absent' (no key) and 'empty' ('') are missing.
    expect($keys)->toContain('absent')
        ->toContain('empty')
        ->not->toContain('present');

    $all = $this->repo->paginate('de', '', 'sec', false, 50);
    expect($all->total())->toBe(3);
});

test('F2-b byGroup with locale filter returns only rows with that locale (PHP-side)', function (): void {
    $lines = $this->repo->byGroup('sec', 'de');

    $keys = $lines->map(fn ($l) => $l->key)->all();

    expect($keys)->toContain('present')
        ->not->toContain('absent')
        ->not->toContain('empty')
        ->and($lines->every(fn ($l) => trim($l->value('de')) !== ''))->toBeTrue();
});

test('F2-c vendor with locale filter returns only rows with that locale (PHP-side)', function (): void {
    Translation::create([
        'group' => 'buttons',
        'key' => 'save',
        'type' => LinguaType::text,
        'text' => ['en' => 'Save', 'de' => 'Speichern'],
        'is_vendor' => true,
        'vendor' => 'flux',
    ]);
    Translation::create([
        'group' => 'buttons',
        'key' => 'cancel',
        'type' => LinguaType::text,
        'text' => ['en' => 'Cancel'],
        'is_vendor' => true,
        'vendor' => 'flux',
    ]);

    $lines = $this->repo->vendor('flux', 'de');
    $keys = $lines->map(fn ($l) => $l->key)->all();

    expect($keys)->toContain('save')
        ->not->toContain('cancel')
        ->and($lines->every(fn ($l) => trim($l->value('de')) !== ''))->toBeTrue();
});

// ── F3: PathGuard on DB-mode install sinks ────────────────────────────────────

test('F3-a BundledTranslationSource::translationsFor rejects traversal locale', function (): void {
    $source = new BundledTranslationSource(config('lingua.base_translations_path'));

    expect(fn () => $source->translationsFor('../../evil'))
        ->toThrow(InvalidArgumentException::class);
});

test('F3-b DatabaseRepository::installLocale rejects traversal locale', function (): void {
    expect(fn () => $this->repo->installLocale('../../evil'))
        ->toThrow(InvalidArgumentException::class);
});
