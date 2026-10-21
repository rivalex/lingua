<?php

use Livewire\Livewire;
use Rivalex\Lingua\Livewire\LanguageSelector;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\LinguaSetting;

it('can get `COMPUTED` property `languages`', function () {
    Livewire::test(LanguageSelector::class)
        ->set('mode', 'sidebar')
        ->assertSet('mode', 'sidebar')
        ->assertCount('languages', Language::count());
});

it('can show the `Language SIDEBAR` selector component', function () {
    Livewire::test(LanguageSelector::class)
        ->set('mode', 'sidebar')
        ->assertSet('mode', 'sidebar')
        ->assertStatus(200)
        ->assertSeeHtml('data-flux-sidebar-group');
});

it('can show the `Language MODAL` selector component', function () {
    Livewire::test(LanguageSelector::class)
        ->set('mode', 'modal')
        ->assertSet('mode', 'modal')
        ->assertStatus(200)
        ->assertSeeHtml('data-flux-modal-trigger');
});

it('can show the `Language DROPDOWN` selector component', function () {
    Livewire::test(LanguageSelector::class)
        ->set('mode', 'dropdown')
        ->assertSet('mode', 'dropdown')
        ->assertStatus(200)
        ->assertSeeHtml('x-data="{ open: false }"');
});

it('can switch the `CURRENT Language`', function () {
    Language::factory()->create(['code' => 'it', 'is_default' => false]);

    expect(app()->getLocale())->toBe('en');
    $component = Livewire::test(LanguageSelector::class);
    $redirect = $component->currentUrl;
    $component->set('mode', 'sidebar')
        ->assertSet('mode', 'sidebar')
        ->call('changeLocale', 'it')
        ->assertRedirect($redirect);
    expect(session()->has('locale'))->toBeTrue()
        ->and(session('locale'))->toBe('it')
        ->and(app()->getLocale())->toBe('it');

    Language::where('code', 'it')->delete();
});

it('rejects `changeLocale` with an unknown locale', function () {
    $component = Livewire::test(LanguageSelector::class);
    $component->call('changeLocale', 'xx_FAKE')
        ->assertNoRedirect();
    expect(session('locale'))->not->toBe('xx_FAKE');
});

// ---------------------------------------------------------------------------
// mount() — explicit showFlags override (line 35)
// ---------------------------------------------------------------------------

it('respects an explicit showFlags=false passed to mount', function (): void {
    LinguaSetting::set(LinguaSetting::KEY_SHOW_FLAGS, true);

    Livewire::test(LanguageSelector::class, ['showFlags' => false])
        ->assertSet('showFlags', false);
});

it('respects an explicit showFlags=true passed to mount', function (): void {
    LinguaSetting::set(LinguaSetting::KEY_SHOW_FLAGS, false);

    Livewire::test(LanguageSelector::class, ['showFlags' => true])
        ->assertSet('showFlags', true);
});

// ---------------------------------------------------------------------------
// refreshLanguagesSelector — #[On('refreshLanguages')] handler (lines 47-50)
// ---------------------------------------------------------------------------

it('handles the refreshLanguages event in menu mode without error', function (): void {
    Livewire::test(LanguageSelector::class)
        ->set('modal', false)
        ->dispatch('refreshLanguages')
        ->assertOk();
});

it('handles the refreshLanguages event in modal mode without error', function (): void {
    Livewire::test(LanguageSelector::class)
        ->set('modal', true)
        ->dispatch('refreshLanguages')
        ->assertOk();
});
