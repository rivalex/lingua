<?php

declare(strict_types=1);

use Livewire\Livewire;
use Rivalex\Lingua\Enums\SelectorMode;
use Rivalex\Lingua\Livewire\Settings;
use Rivalex\Lingua\Models\LinguaSetting;

// ---------------------------------------------------------------------------
// Rendering
// ---------------------------------------------------------------------------

it('renders the settings page', function (): void {
    Livewire::test(Settings::class)
        ->assertOk()
        ->assertSee('Lingua Settings');
});

it('shows the selector section', function (): void {
    Livewire::test(Settings::class)
        ->assertSee('Language Selector');
});

it('exposes all four selector mode options', function (): void {
    $component = Livewire::test(Settings::class);

    foreach (SelectorMode::cases() as $mode) {
        $component->assertSee($mode->label());
    }
});

// ---------------------------------------------------------------------------
// mount() — default values
// ---------------------------------------------------------------------------

it('loads showFlags from config when no DB row exists', function (): void {
    config(['lingua.selector.show_flags' => true]);

    Livewire::test(Settings::class)
        ->assertSet('showFlags', true);
});

it('loads selectorMode from config when no DB row exists', function (): void {
    config(['lingua.selector.mode' => 'modal']);

    Livewire::test(Settings::class)
        ->assertSet('selectorMode', 'modal');
});

it('loads showFlags from DB when a row exists', function (): void {
    LinguaSetting::set(LinguaSetting::KEY_SHOW_FLAGS, false);

    Livewire::test(Settings::class)
        ->assertSet('showFlags', false);
});

it('loads selectorMode from DB when a row exists', function (): void {
    LinguaSetting::set(LinguaSetting::KEY_SELECTOR_MODE, 'dropdown');

    Livewire::test(Settings::class)
        ->assertSet('selectorMode', 'dropdown');
});

// ---------------------------------------------------------------------------
// save()
// ---------------------------------------------------------------------------

it('persists showFlags to the database on save', function (): void {
    Livewire::test(Settings::class)
        ->set('showFlags', false)
        ->call('save');

    expect(LinguaSetting::get(LinguaSetting::KEY_SHOW_FLAGS))->toBeFalse();
});

it('persists selectorMode to the database on save', function (): void {
    Livewire::test(Settings::class)
        ->set('selectorMode', 'modal')
        ->call('save');

    expect(LinguaSetting::get(LinguaSetting::KEY_SELECTOR_MODE))->toBe('modal');
});

it('dispatches a settings-saved browser event after save', function (): void {
    Livewire::test(Settings::class)
        ->call('save')
        ->assertDispatched('settings-saved');
});

it('falls back to sidebar mode when an invalid selectorMode is submitted', function (): void {
    Livewire::test(Settings::class)
        ->set('selectorMode', 'invalid_mode')
        ->call('save');

    expect(LinguaSetting::get(LinguaSetting::KEY_SELECTOR_MODE))->toBe(SelectorMode::Sidebar->value);
});

// ---------------------------------------------------------------------------
// availableModes computed property
// ---------------------------------------------------------------------------

it('returns all four SelectorMode cases from availableModes', function (): void {
    $component = Livewire::test(Settings::class);
    $modes = $component->get('availableModes');

    expect($modes)->toHaveCount(4);
    expect(array_column(array_map(fn ($m) => ['value' => $m->value], $modes), 'value'))
        ->toContain('sidebar', 'modal', 'dropdown', 'headless');
});
