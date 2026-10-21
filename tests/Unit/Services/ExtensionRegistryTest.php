<?php

declare(strict_types=1);

use Rivalex\Lingua\Contracts\LinguaExtensionInterface;
use Rivalex\Lingua\Services\ExtensionRegistry;

// ─────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────

/**
 * Builds a minimal LinguaExtensionInterface anonymous class for testing.
 *
 * @param  array<int, class-string>  $tabs
 * @param  array<int, class-string>  $actions
 * @param  array<int, class-string>  $settings
 * @param  array<int, class-string>  $widgets
 * @param  array<int, array{label:string,route:string,icon:string,active_pattern:string}>  $navItems
 */
function makeExtension(
    array $tabs = [],
    array $actions = [],
    array $settings = [],
    array $widgets = [],
    array $navItems = [],
): LinguaExtensionInterface {
    return new class($tabs, $actions, $settings, $widgets, $navItems) implements LinguaExtensionInterface
    {
        public function __construct(
            private readonly array $tabs,
            private readonly array $actions,
            private readonly array $settings,
            private readonly array $widgets,
            private readonly array $navItems,
        ) {}

        public function navigationItems(): array
        {
            return $this->navItems;
        }

        public function translationTabComponents(): array
        {
            return $this->tabs;
        }

        public function translationActionComponents(): array
        {
            return $this->actions;
        }

        public function settingsTabComponents(): array
        {
            return $this->settings;
        }

        public function dashboardWidgetComponents(): array
        {
            return $this->widgets;
        }
    };
}

/**
 * Tags an already-instantiated extension with 'lingua.extensions'
 * and returns a fresh ExtensionRegistry bound to the test container.
 *
 * @param  list<LinguaExtensionInterface>  $extensions
 */
function registryWith(array $extensions): ExtensionRegistry
{
    foreach ($extensions as $i => $ext) {
        $abstract = "test_lingua_ext_{$i}";
        app()->instance($abstract, $ext);
        app()->tag([$abstract], 'lingua.extensions');
    }

    // Resolve a new instance (bypassing the singleton for isolation).
    return new ExtensionRegistry(app());
}

// ─────────────────────────────────────────────────────────────
// Tests
// ─────────────────────────────────────────────────────────────

it('returns empty arrays when no extensions are tagged', function () {
    $registry = new ExtensionRegistry(app());

    expect($registry->allNavigationItems())->toBe([])
        ->and($registry->allTranslationTabComponents())->toBe([])
        ->and($registry->allTranslationActionComponents())->toBe([])
        ->and($registry->allSettingsTabComponents())->toBe([])
        ->and($registry->allDashboardWidgetComponents())->toBe([]);
})->group('extension-registry');

it('aggregates translation tab components from multiple extensions', function () {
    $registry = registryWith([
        makeExtension(tabs: ['App\\TabA']),
        makeExtension(tabs: ['App\\TabB']),
    ]);

    expect($registry->allTranslationTabComponents())->toBe(['App\\TabA', 'App\\TabB']);
})->group('extension-registry');

it('aggregates translation action components from multiple extensions', function () {
    $registry = registryWith([
        makeExtension(actions: ['App\\ActionA']),
        makeExtension(actions: ['App\\ActionB']),
    ]);

    expect($registry->allTranslationActionComponents())->toBe(['App\\ActionA', 'App\\ActionB']);
})->group('extension-registry');

it('aggregates settings tab components from multiple extensions', function () {
    $registry = registryWith([
        makeExtension(settings: ['App\\SettingsA']),
        makeExtension(settings: ['App\\SettingsB']),
    ]);

    expect($registry->allSettingsTabComponents())->toBe(['App\\SettingsA', 'App\\SettingsB']);
})->group('extension-registry');

it('aggregates dashboard widget components from multiple extensions', function () {
    $registry = registryWith([
        makeExtension(widgets: ['App\\WidgetA']),
        makeExtension(widgets: ['App\\WidgetB']),
    ]);

    expect($registry->allDashboardWidgetComponents())->toBe(['App\\WidgetA', 'App\\WidgetB']);
})->group('extension-registry');

it('aggregates navigation items from multiple extensions', function () {
    $navA = ['label' => 'Pro', 'route' => 'lingua-pro.home', 'icon' => 'sparkles', 'active_pattern' => 'lingua-pro.*'];
    $navB = ['label' => 'Drivers', 'route' => 'lingua-pro.drivers', 'icon' => 'bolt', 'active_pattern' => 'lingua-pro.drivers'];

    $registry = registryWith([
        makeExtension(navItems: [$navA]),
        makeExtension(navItems: [$navB]),
    ]);

    expect($registry->allNavigationItems())->toBe([$navA, $navB]);
})->group('extension-registry');

it('deduplicates identical component class strings across extensions', function () {
    $registry = registryWith([
        makeExtension(tabs: ['App\\TabA', 'App\\TabB']),
        makeExtension(tabs: ['App\\TabB', 'App\\TabC']),
    ]);

    expect($registry->allTranslationTabComponents())->toBe(['App\\TabA', 'App\\TabB', 'App\\TabC']);
})->group('extension-registry');

it('returns empty arrays when the kill switch is disabled', function () {
    config(['lingua.extensions.enabled' => false]);

    $registry = registryWith([
        makeExtension(tabs: ['App\\TabA']),
    ]);

    expect($registry->allTranslationTabComponents())->toBe([]);
})->group('extension-registry');

it('skips non-LinguaExtensionInterface bindings and continues', function () {
    app()->instance('bad_ext', new stdClass);
    app()->tag(['bad_ext'], 'lingua.extensions');

    $goodExt = makeExtension(tabs: ['App\\GoodTab']);
    app()->instance('good_ext', $goodExt);
    app()->tag(['good_ext'], 'lingua.extensions');

    $registry = new ExtensionRegistry(app());

    // Bad extension is skipped; good extension is still aggregated.
    expect($registry->allTranslationTabComponents())->toBe(['App\\GoodTab']);
})->group('extension-registry');

it('swallows exceptions thrown by extension methods', function () {
    $brokenExt = new class implements LinguaExtensionInterface
    {
        public function navigationItems(): array
        {
            return [];
        }

        public function translationTabComponents(): array
        {
            throw new RuntimeException('broken');
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
    };

    app()->instance('broken_ext', $brokenExt);
    app()->tag(['broken_ext'], 'lingua.extensions');

    $registry = new ExtensionRegistry(app());

    // Must not throw — broken extension is treated as empty.
    expect($registry->allTranslationTabComponents())->toBe([]);
})->group('extension-registry');

it('skips malformed navigation items missing required keys', function () {
    $registry = registryWith([
        makeExtension(navItems: [
            ['label' => 'Only label'],   // missing route, icon, active_pattern
            ['label' => 'Full', 'route' => 'lingua-pro.home', 'icon' => 'sparkles', 'active_pattern' => 'lingua-pro.*'],
        ]),
    ]);

    // Only the valid item is returned.
    expect($registry->allNavigationItems())->toHaveCount(1)
        ->and($registry->allNavigationItems()[0]['label'])->toBe('Full');
})->group('extension-registry');
