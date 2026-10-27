<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use Rivalex\Lingua\Http\Middleware\LinguaMiddleware;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Tests\Fixtures\LocaleSwitchProbe;

function runLinguaMiddleware(): void
{
    (new LinguaMiddleware)->handle(Request::create('/'), fn ($request) => response('ok'));
}

it('falls back to the default when the session locale is not installed', function (): void {
    Session::put(config('lingua.session_variable'), 'de');

    runLinguaMiddleware();

    expect(app()->getLocale())->toBe('en')
        ->and(Session::get(config('lingua.session_variable')))->toBe('en');
})->group('session-locale');

it('keeps the session locale when it is installed', function (): void {
    Language::factory()->create(['code' => 'it', 'regional' => 'it_IT', 'is_default' => false]);
    Session::put(config('lingua.session_variable'), 'it');

    runLinguaMiddleware();

    expect(app()->getLocale())->toBe('it')
        ->and(Session::get(config('lingua.session_variable')))->toBe('it');
})->group('session-locale');

it('stores the language code when the locale is switched by regional code', function (): void {
    Language::factory()->create(['code' => 'it', 'regional' => 'it_IT', 'is_default' => false]);

    Livewire::test(LocaleSwitchProbe::class, ['url' => '/'])->call('changeLocale', 'it_IT');

    expect(Session::get(config('lingua.session_variable')))->toBe('it')
        ->and(app()->getLocale())->toBe('it');
})->group('session-locale');
