<?php

use Livewire\Livewire;

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
