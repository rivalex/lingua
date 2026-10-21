<?php

declare(strict_types=1);

use Livewire\Livewire;
use Rivalex\Lingua\Livewire\Translation\Create;
use Rivalex\Lingua\Livewire\Translation\Row;
use Rivalex\Lingua\Livewire\Translations;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

// ── Translations banner ───────────────────────────────────────────────────────

it('shows the Pro upgrade banner in Translations by default', function () {
    Livewire::test(Translations::class)
        ->assertSee('Upgrade to Lingua Pro');
});

it('hides the Pro upgrade banner when suppress_pro_nudge is true', function () {
    config(['lingua.suppress_pro_nudge' => true]);

    Livewire::test(Translations::class)
        ->assertDontSee('Upgrade to Lingua Pro');
});

it('banner links to the configured pro_upgrade_url', function () {
    config(['lingua.pro_upgrade_url' => 'https://lingua.rivalex.com']);

    Livewire::test(Translations::class)
        ->assertSeeHtml('https://lingua.rivalex.com');
});

// ── Row: disabled auto-translate button ───────────────────────────────────────

it('shows disabled auto-translate button in Row for non-default locale', function () {
    $translation = Translation::create([
        'group' => 'test',
        'key' => 'nudge_test_'.uniqid(),
        'type' => 'text',
        'text' => ['en' => 'Default English value'],
        'is_vendor' => false,
        'vendor' => null,
    ]);

    Language::factory()->create(['code' => 'it', 'is_default' => false]);

    Livewire::test(Row::class, [
        'translation' => $translation,
        'currentLocale' => 'it',
    ])->assertSee('Auto-translate');

    $translation->delete();
    Language::where('code', 'it')->delete();
});

it('does not show auto-translate button in Row for default locale', function () {
    $translation = Translation::create([
        'group' => 'test',
        'key' => 'nudge_test_'.uniqid(),
        'type' => 'text',
        'text' => ['en' => 'Default English value'],
        'is_vendor' => false,
        'vendor' => null,
    ]);

    Livewire::test(Row::class, [
        'translation' => $translation,
        'currentLocale' => 'en',
    ])->assertDontSee('Auto-translate');

    $translation->delete();
});

it('does not show auto-translate button in Row when suppress_pro_nudge is true', function () {
    config(['lingua.suppress_pro_nudge' => true]);

    $translation = Translation::create([
        'group' => 'test',
        'key' => 'nudge_test_'.uniqid(),
        'type' => 'text',
        'text' => ['en' => 'Default English value'],
        'is_vendor' => false,
        'vendor' => null,
    ]);

    Language::factory()->create(['code' => 'it', 'is_default' => false]);

    Livewire::test(Row::class, [
        'translation' => $translation,
        'currentLocale' => 'it',
    ])->assertDontSee('Auto-translate');

    $translation->delete();
    Language::where('code', 'it')->delete();
});

// ── Create modal: Pro hint ────────────────────────────────────────────────────

it('shows Pro hint in Create modal by default', function () {
    Livewire::test(Create::class)
        ->assertSee('Upgrade to Lingua Pro');
});

it('hides Pro hint in Create modal when suppress_pro_nudge is true', function () {
    config(['lingua.suppress_pro_nudge' => true]);

    Livewire::test(Create::class)
        ->assertDontSee('Upgrade to Lingua Pro');
});
