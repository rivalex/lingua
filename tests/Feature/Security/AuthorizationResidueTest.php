<?php

declare(strict_types=1);

use Illuminate\Auth\Access\Gate as GateImplementation;
use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Livewire\Component;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Rivalex\Lingua\Contracts\LinguaExtensionInterface;
use Rivalex\Lingua\Http\Controllers\TransferExportController;
use Rivalex\Lingua\LinguaServiceProvider;
use Rivalex\Lingua\Livewire\Import;
use Rivalex\Lingua\Livewire\Settings;
use Rivalex\Lingua\Livewire\Translations;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Services\ExtensionRegistry;
use Rivalex\Lingua\Tests\Fixtures\LocaleSwitchProbe;

/*
 * Authorization residue (gate semantics): the export controller authorizes on its own,
 * the fallback gate needs a user, extension components must use AuthorizesLingua,
 * Import's flow flags are locked and a malformed currentUrl falls back to '/'.
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
 * provider fallback the way boot() does.
 */
function residueGate(string $environment): void
{
    app()->forgetInstance(GateContract::class);
    app()->singleton(GateContract::class, fn ($app) => new GateImplementation(
        $app,
        fn () => call_user_func($app['auth']->userResolver()),
    ));
    Gate::clearResolvedInstance(GateContract::class);

    app()['env'] = $environment;

    $provider = new LinguaServiceProvider(app());
    (new ReflectionMethod($provider, 'registerDefaultGate'))->invoke($provider);
}

/**
 * Component without the AuthorizesLingua trait, returned by an extension.
 */
final class ResidueUnguardedComponent extends Component
{
    public function render(): string
    {
        return '<div>residue-unguarded-marker</div>';
    }
}

test('the export controller authorizes on its own when the route has no can: middleware', function (): void {
    Gate::define('manage-lingua', fn ($user = null) => false);

    Route::get('residue-export', [TransferExportController::class, 'download'])->middleware('web');

    $this->actingAs(new GenericUser(['id' => 1]));

    $this->get('/residue-export?scope=bilingual&filter=all&format=csv&targetLocale=en')->assertForbidden();
});

test('fallback gate in local: a guest is forbidden and an authenticated user is allowed', function (): void {
    residueGate('local');

    Livewire::test(Settings::class)->assertForbidden();

    $this->actingAs(new GenericUser(['id' => 1]));

    Livewire::test(Settings::class)->assertOk();
});

test('an extension component without the trait is not rendered and is reported', function (): void {
    Exceptions::fake();

    app()->instance('residue_ext', new class implements LinguaExtensionInterface
    {
        public function navigationItems(): array
        {
            return [];
        }

        public function translationTabComponents(): array
        {
            return [ResidueUnguardedComponent::class];
        }

        public function translationActionComponents(): array
        {
            return [];
        }

        public function settingsTabComponents(): array
        {
            return [];
        }

        public function dashboardWidgetComponents(): array
        {
            return [];
        }
    });
    app()->tag(['residue_ext'], 'lingua.extensions');
    View::share('linguaExtensions', new ExtensionRegistry(app()));

    Livewire::test(Translations::class)
        ->assertOk()
        ->assertDontSee('residue-unguarded-marker');

    Exceptions::assertReported(
        fn (InvalidArgumentException $e): bool => str_contains($e->getMessage(), ResidueUnguardedComponent::class)
    );
});

test('Import flow flags are locked against client updates', function (): void {
    expect(fn () => Livewire::test(Import::class)->set('previewed', true))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

test('changeLocale falls back to / when currentUrl cannot be parsed', function (): void {
    Language::factory()->create(['code' => 'it', 'is_default' => false]);

    Livewire::test(LocaleSwitchProbe::class, ['url' => '/a:65536'])
        ->call('changeLocale', 'it')
        ->assertRedirect('/');
});
