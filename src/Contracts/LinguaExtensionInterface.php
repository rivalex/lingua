<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Contracts;

/**
 * Extension SPI for lingua add-on packages.
 *
 * Any class tagged with `lingua.extensions` in the Laravel container
 * MUST implement this interface. All methods must return plain arrays
 * and never throw — return an empty array on any failure.
 *
 * Registration example in a third-party ServiceProvider::register():
 *
 * ```php
 * $this->app->singleton(MyExtension::class);
 * $this->app->tag([MyExtension::class], 'lingua.extensions');
 * ```
 */
interface LinguaExtensionInterface
{
    /**
     * Navigation entries to be rendered by the host application's layout
     * via the `<x-lingua::extension-nav-items />` Blade component.
     *
     * Each entry must have the following keys:
     *
     * ```php
     * [
     *     'label'          => 'Pro Dashboard',   // Visible link text
     *     'route'          => 'lingua-pro.home',  // Named Laravel route
     *     'icon'           => 'sparkles',          // Flux icon name (without flux:icon. prefix)
     *     'active_pattern' => 'lingua-pro.*',      // Pattern for request()->routeIs()
     * ]
     * ```
     *
     * @return array<int, array{label: string, route: string, icon: string, active_pattern: string}>
     */
    public function navigationItems(): array;

    /**
     * Livewire component class strings to render in the tab strip
     * beneath the Translations page header.
     *
     * Each string must be a fully-qualified Livewire component class
     * resolvable via the container (e.g. `App\Livewire\MyTab::class`).
     *
     * @return array<int, class-string>
     */
    public function translationTabComponents(): array;

    /**
     * Livewire component class strings to render inside the sticky
     * action toolbar of the Translations page, alongside the core
     * "New Translation" button.
     *
     * @return array<int, class-string>
     */
    public function translationActionComponents(): array;

    /**
     * Livewire component class strings to render on the Settings page,
     * appended below the core selector settings panel.
     *
     * @return array<int, class-string>
     */
    public function settingsTabComponents(): array;

    /**
     * Livewire component class strings to render on the Statistics page
     * (de-facto dashboard), appended after the group breakdown section.
     *
     * @return array<int, class-string>
     */
    public function dashboardWidgetComponents(): array;
}
