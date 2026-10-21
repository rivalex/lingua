<?php

declare(strict_types=1);

use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Rivalex\Lingua\Contracts\LinguaExtensionInterface;
use Rivalex\Lingua\Livewire\Settings;
use Rivalex\Lingua\Livewire\Statistics;
use Rivalex\Lingua\Livewire\Translations;
use Rivalex\Lingua\Services\ExtensionRegistry;

// ─────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────

/**
 * Tags a mock extension and refreshes the shared registry instance
 * so that views receive the updated registry for this test.
 */
function injectExtension(LinguaExtensionInterface $ext): void
{
    app()->instance('test_injected_ext', $ext);
    app()->tag(['test_injected_ext'], 'lingua.extensions');

    // Re-share a fresh registry so the newly-tagged extension is visible
    // to Blade views (View::share was called during boot with the old singleton).
    $registry = new ExtensionRegistry(app());
    View::share('linguaExtensions', $registry);
}

// ─────────────────────────────────────────────────────────────
// No extensions registered — pages must render without errors
// ─────────────────────────────────────────────────────────────

it('renders the translations page without errors when no extensions are registered', function () {
    // Use Livewire::test() — $this->get() fails in Testbench without a host layout.
    Livewire::test(Translations::class)
        ->assertStatus(200);
})->group('extension-injection');

it('renders the settings page without errors when no extensions are registered', function () {
    Livewire::test(Settings::class)
        ->assertStatus(200);
})->group('extension-injection');

it('renders the statistics page without errors when no extensions are registered', function () {
    Livewire::test(Statistics::class)
        ->assertStatus(200);
})->group('extension-injection');

// ─────────────────────────────────────────────────────────────
// $linguaExtensions is shared to all views
// ─────────────────────────────────────────────────────────────

it('shares $linguaExtensions with views as an ExtensionRegistry instance', function () {
    // View::share is called during ServiceProvider::boot() — verify the binding
    // is present without needing to fire an HTTP request.
    expect(view()->shared('linguaExtensions'))
        ->toBeInstanceOf(ExtensionRegistry::class);
})->group('extension-injection');

// ─────────────────────────────────────────────────────────────
// With a tagged extension — pages still render without errors
// ─────────────────────────────────────────────────────────────

it('renders the translations page with an active extension without errors', function () {
    injectExtension(new class implements LinguaExtensionInterface
    {
        public function navigationItems(): array
        {
            return [];
        }

        public function translationTabComponents(): array
        {
            return [];
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

    // Extension returns empty arrays — page renders identically to baseline.
    Livewire::test(Translations::class)
        ->assertStatus(200);
})->group('extension-injection');

it('kill switch disables all extension hooks', function () {
    config(['lingua.extensions.enabled' => false]);

    injectExtension(new class implements LinguaExtensionInterface
    {
        public function navigationItems(): array
        {
            return [['label' => 'Pro', 'route' => 'lingua-pro.home', 'icon' => 'sparkles', 'active_pattern' => 'lingua-pro.*']];
        }

        public function translationTabComponents(): array
        {
            return ['App\\Tab'];
        }

        public function translationActionComponents(): array
        {
            return ['App\\Action'];
        }

        public function settingsTabComponents(): array
        {
            return ['App\\Settings'];
        }

        public function dashboardWidgetComponents(): array
        {
            return ['App\\Widget'];
        }
    });

    $registry = view()->shared('linguaExtensions');

    expect($registry->allNavigationItems())->toBe([])
        ->and($registry->allTranslationTabComponents())->toBe([])
        ->and($registry->allTranslationActionComponents())->toBe([])
        ->and($registry->allSettingsTabComponents())->toBe([])
        ->and($registry->allDashboardWidgetComponents())->toBe([]);
})->group('extension-injection');
