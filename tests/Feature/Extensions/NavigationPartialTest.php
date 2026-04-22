<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ViewErrorBag;
use Rivalex\Lingua\Contracts\LinguaExtensionInterface;
use Rivalex\Lingua\Services\ExtensionRegistry;

// Share empty error bag and a fresh registry before each test so
// the Blade component can be rendered in isolation.
beforeEach(function () {
    view()->share('errors', new ViewErrorBag);
    view()->share('linguaExtensions', new ExtensionRegistry(app()));
});

// ─────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────

/**
 * Registers extensions, shares a fresh registry, and renders the nav component.
 *
 * @param  list<LinguaExtensionInterface>  $extensions
 */
function renderNavWith(array $extensions, string $extraProps = ''): string
{
    foreach ($extensions as $i => $ext) {
        $abstract = "test_nav_ext_{$i}";
        app()->instance($abstract, $ext);
        app()->tag([$abstract], 'lingua.extensions');
    }

    view()->share('linguaExtensions', new ExtensionRegistry(app()));

    return Blade::render("<x-lingua::extension-nav-items {$extraProps}/>");
}

// ─────────────────────────────────────────────────────────────
// Tests
// ─────────────────────────────────────────────────────────────

it('renders nothing when no extensions are registered', function () {
    $html = Blade::render('<x-lingua::extension-nav-items />');

    expect($html)->not->toContain('<a');
})->group('navigation-partial');

it('renders one link when one extension returns one navigation item', function () {
    $html = renderNavWith([
        new class implements LinguaExtensionInterface
        {
            public function navigationItems(): array
            {
                return [
                    [
                        'label' => 'Pro Dashboard',
                        'route' => 'lingua.languages', // use a real named route
                        'icon' => 'sparkles',
                        'active_pattern' => 'lingua.languages',
                    ],
                ];
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
        },
    ]);

    expect($html)
        ->toContain('<a')
        ->toContain('Pro Dashboard');
})->group('navigation-partial');

it('sets aria-current="page" when the route matches the active pattern', function () {
    // Point the test request to lingua.languages so routeIs matches.
    $this->get(route('lingua.languages'));

    $html = renderNavWith([
        new class implements LinguaExtensionInterface
        {
            public function navigationItems(): array
            {
                return [
                    [
                        'label' => 'Languages',
                        'route' => 'lingua.languages',
                        'icon' => 'language',
                        'active_pattern' => 'lingua.languages',
                    ],
                ];
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
        },
    ]);

    expect($html)->toContain('aria-current="page"');
})->group('navigation-partial');

it('does not set aria-current when the route does not match', function () {
    // Visiting a different route.
    $this->get(route('lingua.statistics'));

    $html = renderNavWith([
        new class implements LinguaExtensionInterface
        {
            public function navigationItems(): array
            {
                return [
                    [
                        'label' => 'Languages',
                        'route' => 'lingua.languages',
                        'icon' => 'language',
                        'active_pattern' => 'lingua.languages',
                    ],
                ];
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
        },
    ]);

    expect($html)->not->toContain('aria-current="page"');
})->group('navigation-partial');

it('renders multiple links when multiple extensions each contribute items', function () {
    $makeItem = static fn (string $label, string $route): array => [
        'label' => $label,
        'route' => $route,
        'icon' => 'sparkles',
        'active_pattern' => $route,
    ];

    $makeExt = static fn (string $label, string $route): LinguaExtensionInterface => new class($makeItem($label, $route)) implements LinguaExtensionInterface
    {
        public function __construct(private readonly array $item) {}

        public function navigationItems(): array
        {
            return [$this->item];
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
    };

    $html = renderNavWith([
        $makeExt('Link One', 'lingua.languages'),
        $makeExt('Link Two', 'lingua.settings'),
    ]);

    expect($html)
        ->toContain('Link One')
        ->toContain('Link Two');
})->group('navigation-partial');
