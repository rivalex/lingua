<?php

use Livewire\Livewire;
use Rivalex\Lingua\Livewire\Languages;
use Rivalex\Lingua\Livewire\Language\Table;
use Rivalex\Lingua\Models\Language;

it('can reach the translations component page', function () {
    Livewire::test('lingua::translations')
            ->assertStatus(200);
});

