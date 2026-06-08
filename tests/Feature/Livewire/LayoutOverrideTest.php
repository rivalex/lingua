<?php

declare(strict_types=1);

use Illuminate\View\View;
use Livewire\Livewire;
use Rivalex\Lingua\Livewire\Languages;
use Rivalex\Lingua\Livewire\Settings;
use Rivalex\Lingua\Livewire\Statistics;
use Rivalex\Lingua\Livewire\Translations;

it('has layout config set to null by default', function () {
    expect(config('lingua.layout'))->toBeNull();
});

// ---------------------------------------------------------------------------
// Null layout — existing Livewire embed rendering must stay green
// ---------------------------------------------------------------------------

it('Languages renders without error when lingua.layout is null', function () {
    config(['lingua.layout' => null]);
    Livewire::test(Languages::class)->assertOk();
});

it('Translations renders without error when lingua.layout is null', function () {
    config(['lingua.layout' => null]);
    Livewire::test(Translations::class)->assertOk();
});

it('Statistics renders without error when lingua.layout is null', function () {
    config(['lingua.layout' => null]);
    Livewire::test(Statistics::class)->assertOk();
});

it('Settings renders without error when lingua.layout is null', function () {
    config(['lingua.layout' => null]);
    Livewire::test(Settings::class)->assertOk();
});

// ---------------------------------------------------------------------------
// Layout configured — render() must return a View instance (layout applied
// at HTTP layer; Livewire::test() skips layout rendering by design).
// ---------------------------------------------------------------------------

it('Languages render() returns a View when layout is configured', function () {
    config(['lingua.layout' => 'components.layouts.app']);

    $result = app()->make(Languages::class)->render();

    expect($result)->toBeInstanceOf(View::class);
});

it('Translations render() returns a View when layout is configured', function () {
    config(['lingua.layout' => 'components.layouts.app']);

    $result = app()->make(Translations::class)->render();

    expect($result)->toBeInstanceOf(View::class);
});

it('Statistics render() returns a View when layout is configured', function () {
    config(['lingua.layout' => 'components.layouts.app']);

    $result = app()->make(Statistics::class)->render();

    expect($result)->toBeInstanceOf(View::class);
});

it('Settings render() returns a View when layout is configured', function () {
    config(['lingua.layout' => 'components.layouts.app']);

    $result = app()->make(Settings::class)->render();

    expect($result)->toBeInstanceOf(View::class);
});
