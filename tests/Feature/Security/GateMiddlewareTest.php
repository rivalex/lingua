<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;

/*
 * F9 — configurable opt-in authorization gate (config('lingua.gate')).
 *
 * routes/web.php computes its middleware array at load time. The package routes
 * are loaded once at boot with the default config (gate = null), so a config()
 * change inside a test body cannot retro-actively alter those already-registered
 * routes. To exercise the gate we re-require routes/web.php under a fresh, unique
 * prefix AFTER setting config — this rebuilds the middleware array from test-time
 * config and registers distinct URLs that the boot-time routes do not shadow.
 *
 * A minimal host layout (fixtures/test-layout.blade.php) is registered so the
 * full-page Livewire route renders a clean 200 under Testbench; without it the
 * page 500s for lack of an app layout, independent of the gate.
 */

beforeEach(function (): void {
    View::addNamespace('linguatest', __DIR__.'/fixtures');
    config(['lingua.layout' => 'linguatest::test-layout']);
});

/**
 * Re-register the package routes with the given gate value under a unique prefix.
 *
 * @return string the freshly registered route prefix
 */
function registerLinguaRoutesWithGate(?string $gate): string
{
    $prefix = 'lingua_'.bin2hex(random_bytes(4));

    config([
        'lingua.gate' => $gate,
        'lingua.routes_prefix' => $prefix,
    ]);

    require __DIR__.'/../../../routes/web.php';
    app('router')->getRoutes()->refreshNameLookups();

    return $prefix;
}

test('gate disabled (default): an authenticated user can access the admin page', function (): void {
    $prefix = registerLinguaRoutesWithGate(null);

    $this->actingAs(new GenericUser(['id' => 1]));

    $this->get('/'.$prefix.'/languages')->assertStatus(200);
});

test('gate enabled: a user who passes the gate can access the admin page', function (): void {
    Gate::define('lingua-admin', fn () => true);

    $prefix = registerLinguaRoutesWithGate('lingua-admin');

    $this->actingAs(new GenericUser(['id' => 1]));

    $this->get('/'.$prefix.'/languages')->assertStatus(200);
});

test('gate enabled: a user who fails the gate is denied from the admin page', function (): void {
    Gate::define('lingua-admin', fn () => false);

    $prefix = registerLinguaRoutesWithGate('lingua-admin');

    $this->actingAs(new GenericUser(['id' => 1]));

    // 403 is emitted by the `can` middleware before the page is rendered.
    $this->get('/'.$prefix.'/languages')->assertStatus(403);
});

test('assets route is unaffected by the gate', function (): void {
    Gate::define('lingua-admin', fn () => false);

    $prefix = registerLinguaRoutesWithGate('lingua-admin');

    // No authentication, no gate — assets are intentionally public.
    $this->get('/'.$prefix.'/assets/css/lingua.min.css')->assertStatus(200);
});
