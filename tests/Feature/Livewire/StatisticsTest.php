<?php

declare(strict_types=1);

use Livewire\Livewire;
use Rivalex\Lingua\Livewire\Statistics;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Create a Language record for Italian (used across multiple tests).
 * The seeder already creates 'en' as the default, so we only add 'it' here.
 */
function createItalianLanguage(): Language
{
    return Language::factory()->create([
        'code' => 'it',
        'regional' => 'IT',
        'type' => 'locale',
        'name' => 'Italian',
        'native' => 'Italiano',
        'direction' => 'ltr',
        'is_default' => false,
        'sort' => 99,
    ]);
}

// ---------------------------------------------------------------------------
// Basic rendering
// ---------------------------------------------------------------------------

it('renders the statistics page', function (): void {
    Livewire::test(Statistics::class)
        ->assertOk()
        ->assertSee('Translation Statistics');
});

it('shows total key and group counts in the header', function (): void {
    $total = Translation::where('is_vendor', false)->count();
    $groups = Translation::where('is_vendor', false)->distinct('group')->count('group');

    Livewire::test(Statistics::class)
        ->assertSee((string) $total)
        ->assertSee((string) $groups);
});

// ---------------------------------------------------------------------------
// totalKeys
// ---------------------------------------------------------------------------

it('counts total keys correctly', function (): void {
    Translation::query()->delete();

    foreach (range(1, 5) as $i) {
        Translation::create([
            'group' => 'test', 'key' => "key_{$i}", 'type' => 'text',
            'text' => ['en' => "Value {$i}"], 'is_vendor' => false, 'vendor' => null,
        ]);
    }

    expect(Livewire::test(Statistics::class)->instance()->totalKeys)->toBe(5);
});

it('excludes vendor keys by default', function (): void {
    Translation::query()->delete();

    foreach (range(1, 3) as $i) {
        Translation::create([
            'group' => 'core', 'key' => "core_{$i}", 'type' => 'text',
            'text' => ['en' => "Core {$i}"], 'is_vendor' => false, 'vendor' => null,
        ]);
    }

    foreach (range(1, 2) as $i) {
        Translation::create([
            'group' => 'vendor', 'key' => "vendor_{$i}", 'type' => 'text',
            'text' => ['en' => "Vendor {$i}"], 'is_vendor' => true, 'vendor' => 'some-package',
        ]);
    }

    expect(Livewire::test(Statistics::class)->instance()->totalKeys)->toBe(3);
});

it('includes vendor keys when toggled', function (): void {
    Translation::query()->delete();

    foreach (range(1, 3) as $i) {
        Translation::create([
            'group' => 'core', 'key' => "core_{$i}", 'type' => 'text',
            'text' => ['en' => "Core {$i}"], 'is_vendor' => false, 'vendor' => null,
        ]);
    }

    foreach (range(1, 2) as $i) {
        Translation::create([
            'group' => 'vendor', 'key' => "vendor_{$i}", 'type' => 'text',
            'text' => ['en' => "Vendor {$i}"], 'is_vendor' => true, 'vendor' => 'some-package',
        ]);
    }

    $component = Livewire::test(Statistics::class);
    $component->call('toggleVendor');

    expect($component->instance()->totalKeys)->toBe(5);
});

// ---------------------------------------------------------------------------
// coverageStats
// ---------------------------------------------------------------------------

it('calculates coverage percentage correctly', function (): void {
    createItalianLanguage();
    Translation::query()->delete();

    // Key 1: translated in both en and it
    Translation::create([
        'group' => 'auth', 'key' => 'login', 'type' => 'text',
        'text' => ['en' => 'Login', 'it' => 'Accedi'],
        'is_vendor' => false, 'vendor' => null,
    ]);

    // Key 2: only translated in en, missing in it
    Translation::create([
        'group' => 'auth', 'key' => 'logout', 'type' => 'text',
        'text' => ['en' => 'Logout'],
        'is_vendor' => false, 'vendor' => null,
    ]);

    $component = Livewire::test(Statistics::class);
    $stats = $component->instance()->coverageStats;
    $itStats = $stats->firstWhere('language.code', 'it');

    expect($itStats['translated'])->toBe(1)
        ->and($itStats['missing'])->toBe(1)
        ->and($itStats['percentage'])->toBe(50.0);
});

it('marks a key as missing when the locale value is an empty string', function (): void {
    createItalianLanguage();
    Translation::query()->delete();

    Translation::create([
        'group' => 'auth', 'key' => 'login', 'type' => 'text',
        'text' => ['en' => 'Hello', 'it' => ''],
        'is_vendor' => false, 'vendor' => null,
    ]);

    $stats = Livewire::test(Statistics::class)->instance()->coverageStats;
    $itStats = $stats->firstWhere('language.code', 'it');

    expect($itStats['missing'])->toBe(1);
});

it('marks a key as missing when the locale value is a whitespace-only string', function (): void {
    createItalianLanguage();
    Translation::query()->delete();

    Translation::create([
        'group' => 'auth', 'key' => 'login', 'type' => 'text',
        'text' => ['en' => 'Hello', 'it' => '   '],
        'is_vendor' => false, 'vendor' => null,
    ]);

    $stats = Livewire::test(Statistics::class)->instance()->coverageStats;
    $itStats = $stats->firstWhere('language.code', 'it');

    expect($itStats['missing'])->toBe(1);
});

it('marks a key as missing when the locale value is null', function (): void {
    createItalianLanguage();
    Translation::query()->delete();

    Translation::create([
        'group' => 'auth', 'key' => 'login', 'type' => 'text',
        'text' => ['en' => 'Hello', 'it' => null],
        'is_vendor' => false, 'vendor' => null,
    ]);

    $stats = Livewire::test(Statistics::class)->instance()->coverageStats;
    $itStats = $stats->firstWhere('language.code', 'it');

    expect($itStats['missing'])->toBe(1);
});

it('marks a key as missing when the locale key is absent from the text array', function (): void {
    createItalianLanguage();
    Translation::query()->delete();

    Translation::create([
        'group' => 'auth', 'key' => 'login', 'type' => 'text',
        'text' => ['en' => 'Hello'],    // 'it' key absent
        'is_vendor' => false, 'vendor' => null,
    ]);

    $stats = Livewire::test(Statistics::class)->instance()->coverageStats;
    $itStats = $stats->firstWhere('language.code', 'it');

    expect($itStats['missing'])->toBe(1);
});

it('reports zero percentage when there are no translation keys', function (): void {
    Translation::query()->delete();

    $stats = Livewire::test(Statistics::class)->instance()->coverageStats;

    expect($stats->every(fn (array $s): bool => $s['percentage'] === 0.0))->toBeTrue();
});

// ---------------------------------------------------------------------------
// missingKeys / panel toggle
// ---------------------------------------------------------------------------

it('starts with no locale expanded', function (): void {
    Livewire::test(Statistics::class)
        ->assertSet('expandedLocale', null);
});

it('toggles the missing-keys panel open and closed for a locale', function (): void {
    createItalianLanguage();

    Livewire::test(Statistics::class)
        ->assertSet('expandedLocale', null)
        ->call('toggleMissingKeys', 'it')
        ->assertSet('expandedLocale', 'it')
        ->call('toggleMissingKeys', 'it')
        ->assertSet('expandedLocale', null);
});

it('returns only missing keys for the expanded locale', function (): void {
    createItalianLanguage();
    Translation::query()->delete();

    Translation::create([
        'group' => 'auth', 'key' => 'login', 'type' => 'text',
        'text' => ['en' => 'Login'],    // 'it' absent → missing
        'is_vendor' => false, 'vendor' => null,
    ]);

    $component = Livewire::test(Statistics::class);
    $component->call('toggleMissingKeys', 'it');

    $missing = $component->instance()->missingKeys;

    expect($missing)->toHaveCount(1)
        ->and($missing->first()['group'])->toBe('auth')
        ->and($missing->first()['key'])->toBe('login');
});

it('returns an empty collection for missingKeys when no locale is expanded', function (): void {
    $missing = Livewire::test(Statistics::class)->instance()->missingKeys;

    expect($missing)->toBeEmpty();
});

// ---------------------------------------------------------------------------
// groupBreakdown
// ---------------------------------------------------------------------------

it('groups translations by group name in the breakdown', function (): void {
    Translation::query()->delete();

    Translation::create([
        'group' => 'auth', 'key' => 'login', 'type' => 'text',
        'text' => ['en' => 'Login'], 'is_vendor' => false, 'vendor' => null,
    ]);
    Translation::create([
        'group' => 'auth', 'key' => 'logout', 'type' => 'text',
        'text' => ['en' => 'Logout'], 'is_vendor' => false, 'vendor' => null,
    ]);
    Translation::create([
        'group' => 'nav', 'key' => 'home', 'type' => 'text',
        'text' => ['en' => 'Home'], 'is_vendor' => false, 'vendor' => null,
    ]);

    $breakdown = Livewire::test(Statistics::class)->instance()->groupBreakdown;

    expect($breakdown)->toHaveCount(2)
        ->and($breakdown->get('auth')['total'])->toBe(2)
        ->and($breakdown->get('nav')['total'])->toBe(1);
});

// ---------------------------------------------------------------------------
// Vendor toggle — cache invalidation
// ---------------------------------------------------------------------------

it('updates totalKeys after toggling vendor inclusion', function (): void {
    Translation::query()->delete();

    foreach (range(1, 2) as $i) {
        Translation::create([
            'group' => 'core', 'key' => "core_{$i}", 'type' => 'text',
            'text' => ['en' => "Core {$i}"], 'is_vendor' => false, 'vendor' => null,
        ]);
    }

    Translation::create([
        'group' => 'vendor', 'key' => 'vendor_1', 'type' => 'text',
        'text' => ['en' => 'Vendor 1'], 'is_vendor' => true, 'vendor' => 'pkg',
    ]);

    $component = Livewire::test(Statistics::class);

    expect($component->instance()->totalKeys)->toBe(2);

    $component->call('toggleVendor');

    expect($component->instance()->totalKeys)->toBe(3);
});
