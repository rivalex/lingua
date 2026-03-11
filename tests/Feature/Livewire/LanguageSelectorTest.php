<?php

use Livewire\Livewire;
use Rivalex\Lingua\Livewire\LanguageSelector;

it('can get `COMPUTED` property `languages`', function () {
    Livewire::test(LanguageSelector::class)
        ->set('mode', 'sidebar')
        ->assertSet('mode', 'sidebar')
        ->assertCount('languages', 1);
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
        ->assertSeeHtml('data-flux-dropdown');
});

it('can switch the `CURRENT Language`', function () {
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
});
