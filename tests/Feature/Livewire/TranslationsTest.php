<?php

use Livewire\Livewire;

it('can reach the translations component page', function () {
    Livewire::test('lingua::translations')
        ->assertStatus(200);
});
