<?php

declare(strict_types=1);

use Livewire\Livewire;
use Rivalex\Lingua\Models\LinguaSetting;

it('renders `FLAG icon` when flags are `ENABLED`', function () {
    Livewire::test('lingua::selector.icon')
        ->assertStatus(200)
        ->set('showFlags', true)
        ->assertSeeHtml('<svg');
});

it('renders `TEXT icon` when flags are `DISABLED`', function () {
    Livewire::test('lingua::selector.icon')
        ->assertStatus(200)
        ->set('showFlags', false)
        ->assertDontSeeHtml('<svg')
        ->assertSee('en');
});

// ---------------------------------------------------------------------------
// DB setting priority chain
// ---------------------------------------------------------------------------

it('uses DB show_flags setting when no explicit prop is passed', function (): void {
    LinguaSetting::set(LinguaSetting::KEY_SHOW_FLAGS, false);

    Livewire::test('lingua::selector.icon')
        ->assertSet('showFlags', false)
        ->assertDontSeeHtml('<svg')
        ->assertSee('en');
});

it('uses explicit prop over DB setting when prop is provided', function (): void {
    LinguaSetting::set(LinguaSetting::KEY_SHOW_FLAGS, false);

    Livewire::test('lingua::selector.icon', ['showFlags' => true])
        ->assertSet('showFlags', true)
        ->assertSeeHtml('<svg');
});

it('falls back to config when no DB record exists for show_flags', function (): void {
    config(['lingua.selector.show_flags' => false]);

    Livewire::test('lingua::selector.icon')
        ->assertSet('showFlags', false);
});
