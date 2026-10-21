<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Rivalex\Lingua\Facades\Lingua;
use Rivalex\Lingua\Livewire\LanguageSelector;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\LinguaSetting;

// These tests verify that Lingua degrades gracefully (safe defaults, no exceptions)
// when its DB tables are absent — the window between `lingua:uninstall` and
// `composer remove rivalex/lingua`, or before migrations have been run.

// ── Missing-tables guard ──────────────────────────────────────────────────────

describe('when Lingua tables are absent', function (): void {
    beforeEach(function (): void {
        Schema::dropIfExists('language_lines');
        Schema::dropIfExists('lingua_settings');
        Schema::dropIfExists('languages');
    });

    it('Lingua::getLocaleName returns empty string', function (): void {
        expect(Lingua::getLocaleName('en'))->toBe('');
    });

    it('Lingua::getLocaleNative returns empty string', function (): void {
        expect(Lingua::getLocaleNative('en'))->toBe('');
    });

    it('Lingua::getDirection returns ltr', function (): void {
        expect(Lingua::getDirection('en'))->toBe('ltr');
    });

    it('Lingua::isDefaultLocale returns false', function (): void {
        expect(Lingua::isDefaultLocale('en'))->toBeFalse();
    });

    it('Lingua::hasLocale returns false', function (): void {
        expect(Lingua::hasLocale('en'))->toBeFalse();
    });

    it('Lingua::getDefaultLocale returns config fallback', function (): void {
        config(['lingua.default_locale' => 'en']);
        expect(Lingua::getDefaultLocale())->toBe('en');
    });

    it('LinguaSetting::get returns default when lingua_settings missing', function (): void {
        expect(LinguaSetting::get('selector.mode', 'sidebar'))->toBe('sidebar');
        expect(LinguaSetting::get('selector.show_flags', true))->toBeTrue();
    });

    it('LanguageSelector renders without exception', function (): void {
        Livewire::test(LanguageSelector::class)
            ->assertOk();
    });

    it('LanguageSelector languages computed returns empty collection', function (): void {
        $component = Livewire::test(LanguageSelector::class);
        $component->assertOk();
        expect($component->get('languages'))->toBeEmpty();
    });
});

// ── Regression: happy path still works when tables exist ─────────────────────

describe('when Lingua tables are present', function (): void {
    it('Lingua::hasLocale returns true for a seeded language', function (): void {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);
        expect(Lingua::hasLocale('en'))->toBeTrue();
        expect(Lingua::isDefaultLocale('en'))->toBeTrue();
    });

    it('Lingua::getLocaleName returns real value for a seeded language', function (): void {
        Language::factory()->create(['code' => 'fr', 'name' => 'French', 'is_default' => false]);
        expect(Lingua::getLocaleName('fr'))->toBe('French');
    });
});
