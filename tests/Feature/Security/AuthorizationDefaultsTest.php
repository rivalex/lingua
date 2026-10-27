<?php

declare(strict_types=1);

use Illuminate\Auth\Access\Gate as GateImplementation;
use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Rivalex\Lingua\Lingua;
use Rivalex\Lingua\LinguaServiceProvider;
use Rivalex\Lingua\Livewire\LanguageSelector;
use Rivalex\Lingua\Livewire\Selector\Icon;
use Rivalex\Lingua\Livewire\Settings;
use Rivalex\Lingua\Livewire\Translation\Update;
use Rivalex\Lingua\Models\Translation;

/*
 * Deny by default authorization.
 *
 * The suite TestCase defines 'manage-lingua' as allow-all. Every test here swaps the
 * gate for a pristine instance, so each one controls exactly what the host defined,
 * then runs the provider fallback the way boot() does.
 */

beforeEach(function (): void {
    View::addNamespace('linguatest', __DIR__.'/fixtures');
    config(['lingua.layout' => 'linguatest::test-layout']);
    $this->originalEnvironment = app()->environment();
});

afterEach(function (): void {
    app()['env'] = $this->originalEnvironment;
});

/**
 * Replace the gate with a pristine instance, set the environment and run the
 * provider fallback. $hostDefinition simulates the host's Gate::define().
 */
function pristineGate(string $environment, ?Closure $hostDefinition = null, string $ability = 'manage-lingua'): void
{
    app()->forgetInstance(GateContract::class);
    app()->singleton(GateContract::class, fn ($app) => new GateImplementation(
        $app,
        fn () => call_user_func($app['auth']->userResolver()),
    ));
    Gate::clearResolvedInstance(GateContract::class);

    app()['env'] = $environment;

    if ($hostDefinition !== null) {
        Gate::define($ability, $hostDefinition);
    }

    $provider = new LinguaServiceProvider(app());
    (new ReflectionMethod($provider, 'registerDefaultGate'))->invoke($provider);
}

function linguaUser(int $id = 1): GenericUser
{
    return new GenericUser(['id' => $id]);
}

test('production without a host definition: admin page, export and components are forbidden', function (): void {
    pristineGate('production');

    $this->actingAs(linguaUser());

    $this->get(route('lingua.languages'))->assertForbidden();
    $this->get(route('lingua.transfer.export'))->assertForbidden();

    Livewire::test(Settings::class)->assertForbidden();
});

test('host defines the ability for one user: that user is allowed, another is forbidden', function (): void {
    pristineGate('production', fn ($user = null) => $user !== null && $user->getAuthIdentifier() === 1);

    $this->actingAs(linguaUser(1));
    $this->get(route('lingua.languages'))->assertOk();

    $this->actingAs(linguaUser(2));
    $this->get(route('lingua.languages'))->assertForbidden();
});

test('local environment without a host definition: allowed', function (): void {
    pristineGate('local');

    $this->actingAs(linguaUser());

    $this->get(route('lingua.languages'))->assertOk();
});

test('empty gate is an explicit opt-out: an authenticated user is allowed', function (): void {
    config(['lingua.gate' => '']);
    pristineGate('production');

    $this->actingAs(linguaUser());

    // Rebuild the routes from the test-time config under a fresh prefix.
    $prefix = 'lingua_'.bin2hex(random_bytes(4));
    config(['lingua.routes_prefix' => $prefix]);
    require __DIR__.'/../../../routes/web.php';
    app('router')->getRoutes()->refreshNameLookups();

    $this->get('/'.$prefix.'/languages')->assertOk();
    Livewire::test(Settings::class)->assertOk();
});

test('embedded component outside the routes is forbidden for a user the gate denies', function (): void {
    pristineGate('production', fn ($user = null) => false);

    $translation = Translation::create([
        'group' => 'test',
        'key' => 'embed_'.uniqid(),
        'type' => 'text',
        'text' => ['en' => 'Original text'],
        'is_vendor' => false,
        'vendor' => null,
    ]);

    $this->actingAs(linguaUser());

    Livewire::test(Update::class, [
        'translationIdentity' => $translation->group.'|'.$translation->key.'|0|',
        'currentLocale' => 'en',
    ])->assertForbidden();
});

test('the public selector components still mount for a guest', function (): void {
    pristineGate('production', fn ($user = null) => false);

    Livewire::test(LanguageSelector::class)->assertOk();
    Livewire::test(Icon::class)->assertOk();
});

test('Lingua::gate resolves null to the default ability and opts out on empty or false', function (): void {
    config(['lingua.gate' => null]);
    expect(Lingua::gate())->toBe('manage-lingua');

    config(['lingua.gate' => '']);
    expect(Lingua::gate())->toBeNull();

    config(['lingua.gate' => false]);
    expect(Lingua::gate())->toBeNull();

    config(['lingua.gate' => 'custom']);
    expect(Lingua::gate())->toBe('custom');
});

test('legacy null gate config in production: forbidden and the route carries can:manage-lingua', function (): void {
    config(['lingua.gate' => null]);
    pristineGate('production');

    expect(Gate::has('manage-lingua'))->toBeTrue();

    $this->actingAs(linguaUser());

    $prefix = 'lingua_'.bin2hex(random_bytes(4));
    config(['lingua.routes_prefix' => $prefix]);
    require __DIR__.'/../../../routes/web.php';
    app('router')->getRoutes()->refreshNameLookups();

    $route = app('router')->getRoutes()->match(Request::create('/'.$prefix.'/languages'));
    expect($route->gatherMiddleware())->toContain('can:manage-lingua');

    $this->get('/'.$prefix.'/languages')->assertForbidden();
    Livewire::test(Settings::class)->assertForbidden();
});

test('false gate is an opt-out: routes carry no can: and the component check is skipped', function (): void {
    config(['lingua.gate' => false]);
    pristineGate('production');

    expect(Gate::has('manage-lingua'))->toBeFalse();

    $this->actingAs(linguaUser());

    $prefix = 'lingua_'.bin2hex(random_bytes(4));
    config(['lingua.routes_prefix' => $prefix]);
    require __DIR__.'/../../../routes/web.php';
    app('router')->getRoutes()->refreshNameLookups();

    $route = app('router')->getRoutes()->match(Request::create('/'.$prefix.'/languages'));
    $can = array_filter($route->gatherMiddleware(), fn ($m) => is_string($m) && str_starts_with($m, 'can:'));
    expect($can)->toBeEmpty();

    $this->get('/'.$prefix.'/languages')->assertOk();
    Livewire::test(Settings::class)->assertOk();
});

test('the fallback gate is defined at boot when the host defines nothing', function (): void {
    pristineGate('production');

    expect(Gate::has('manage-lingua'))->toBeTrue();
});
