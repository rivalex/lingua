<?php

use Livewire\Livewire;

it('renders FLAG icon when flags are enabled', function () {
    Livewire::test('lingua::selector.icon')
        ->assertStatus(200)
        ->set('showFlags', true)
        ->assertSeeHtml('<svg');
});

it('renders TEXT icon when flags are disabled', function () {
    Livewire::test('lingua::selector.icon')
        ->assertStatus(200)
        ->set('showFlags', false)
        ->assertDontSeeHtml('<svg')
        ->assertSee('en');
});
