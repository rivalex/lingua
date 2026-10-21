<?php

declare(strict_types=1);

use Livewire\Livewire;
use Rivalex\Lingua\Livewire\Languages;
use Rivalex\Lingua\Livewire\Settings;
use Rivalex\Lingua\Livewire\Statistics;
use Rivalex\Lingua\Livewire\Translations;

// ---------------------------------------------------------------------------
// Verifies that Lingua page components can be embedded inline
// (<livewire:lingua::languages />) without going through Lingua routes.
// Livewire::test() simulates embed mode — no layout, no routing required.
// ---------------------------------------------------------------------------

it('Languages renders inline without routing', function () {
    Livewire::test(Languages::class)
        ->assertOk()
        ->assertSeeHtml('class="lingua"');
});

it('Translations renders inline without routing', function () {
    Livewire::test(Translations::class)
        ->assertOk()
        ->assertSeeHtml('class="lingua"');
});

it('Statistics renders inline without routing', function () {
    Livewire::test(Statistics::class)
        ->assertOk()
        ->assertSeeHtml('class="lingua"');
});

it('Settings renders inline without routing', function () {
    Livewire::test(Settings::class)
        ->assertOk()
        ->assertSeeHtml('class="lingua"');
});

it('embedded components render correctly regardless of navigate config', function () {
    foreach ([false, true] as $navigate) {
        config(['lingua.navigate' => $navigate]);

        Livewire::test(Languages::class)->assertOk();
        Livewire::test(Translations::class)->assertOk();
    }
});

it('embedded components render correctly when layout is null', function () {
    config(['lingua.layout' => null]);

    Livewire::test(Languages::class)->assertOk();
    Livewire::test(Translations::class)->assertOk();
    Livewire::test(Statistics::class)->assertOk();
    Livewire::test(Settings::class)->assertOk();
});
