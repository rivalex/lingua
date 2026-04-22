<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Services;

use Illuminate\Contracts\Container\Container;
use Rivalex\Lingua\Contracts\LinguaExtensionInterface;
use Throwable;

/**
 * Collects and aggregates all tagged lingua extension implementations.
 *
 * Resolved as a singleton. Memoizes the extension list after the first
 * call to {@see all()} so extensions are only resolved once per request.
 *
 * Extensions register themselves by tagging their implementation class
 * with the `lingua.extensions` container tag in their own ServiceProvider.
 */
final class ExtensionRegistry
{
    /** @var list<LinguaExtensionInterface>|null Memoized list of resolved extensions. */
    private ?array $resolved = null;

    public function __construct(
        private readonly Container $container,
    ) {}

    /**
     * Returns all resolved, conformant extension instances.
     *
     * Returns an empty array when:
     * - No implementations are tagged.
     * - `config('lingua.extensions.enabled')` is falsy (kill switch).
     *
     * Non-conformant bindings (not implementing LinguaExtensionInterface)
     * are skipped and logged via {@see report()}.
     *
     * @return list<LinguaExtensionInterface>
     */
    public function all(): array
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        if (! config('lingua.extensions.enabled', true)) {
            return $this->resolved = [];
        }

        $extensions = [];

        foreach ($this->container->tagged('lingua.extensions') as $extension) {
            if (! $extension instanceof LinguaExtensionInterface) {
                report(new \RuntimeException(
                    sprintf(
                        '[Lingua] Extension "%s" does not implement LinguaExtensionInterface and was skipped.',
                        get_class($extension),
                    )
                ));

                continue;
            }

            $extensions[] = $extension;
        }

        return $this->resolved = $extensions;
    }

    /**
     * Returns aggregated navigation items from all registered extensions.
     *
     * Entries with missing required keys (label, route, icon, active_pattern)
     * are silently skipped and logged. Duplicate entries are preserved as-is
     * (nav items are unique objects, not class strings).
     *
     * @return array<int, array{label: string, route: string, icon: string, active_pattern: string}>
     */
    public function allNavigationItems(): array
    {
        $items = [];

        foreach ($this->all() as $extension) {
            try {
                foreach ($extension->navigationItems() as $item) {
                    if (! $this->isValidNavItem($item)) {
                        report(new \RuntimeException(
                            sprintf(
                                '[Lingua] Extension "%s" returned a malformed navigation item (missing required keys).',
                                get_class($extension),
                            )
                        ));

                        continue;
                    }

                    $items[] = $item;
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $items;
    }

    /**
     * Returns aggregated Livewire component class strings for the
     * Translations page tab strip. Duplicates removed.
     *
     * @return array<int, class-string>
     */
    public function allTranslationTabComponents(): array
    {
        return $this->aggregateComponents(
            fn (LinguaExtensionInterface $ext): array => $ext->translationTabComponents()
        );
    }

    /**
     * Returns aggregated Livewire component class strings for the
     * Translations page action toolbar. Duplicates removed.
     *
     * @return array<int, class-string>
     */
    public function allTranslationActionComponents(): array
    {
        return $this->aggregateComponents(
            fn (LinguaExtensionInterface $ext): array => $ext->translationActionComponents()
        );
    }

    /**
     * Returns aggregated Livewire component class strings for the
     * Settings page panels. Duplicates removed.
     *
     * @return array<int, class-string>
     */
    public function allSettingsTabComponents(): array
    {
        return $this->aggregateComponents(
            fn (LinguaExtensionInterface $ext): array => $ext->settingsTabComponents()
        );
    }

    /**
     * Returns aggregated Livewire component class strings for the
     * Statistics page (de-facto dashboard) widget area. Duplicates removed.
     *
     * @return array<int, class-string>
     */
    public function allDashboardWidgetComponents(): array
    {
        return $this->aggregateComponents(
            fn (LinguaExtensionInterface $ext): array => $ext->dashboardWidgetComponents()
        );
    }

    /**
     * Calls a method on each extension, merges results, and deduplicates.
     *
     * Any exception thrown by an extension method is caught, passed to
     * {@see report()}, and treated as an empty return — one broken
     * extension must not take down the rest of the UI.
     *
     * @param  callable(LinguaExtensionInterface): array<int, class-string>  $extractor
     * @return array<int, class-string>
     */
    private function aggregateComponents(callable $extractor): array
    {
        $components = [];

        foreach ($this->all() as $extension) {
            try {
                $components = array_merge($components, $extractor($extension));
            } catch (Throwable $e) {
                report($e);
            }
        }

        return array_values(array_unique($components));
    }

    /**
     * Validates that a navigation item array contains all required string keys.
     */
    private function isValidNavItem(mixed $item): bool
    {
        if (! is_array($item)) {
            return false;
        }

        foreach (['label', 'route', 'icon', 'active_pattern'] as $key) {
            if (! isset($item[$key]) || ! is_string($item[$key])) {
                return false;
            }
        }

        return true;
    }
}
