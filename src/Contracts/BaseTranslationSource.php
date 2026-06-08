<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Contracts;

/**
 * Phase 2 extension point: a source of bundled base translations.
 *
 * Implementations return translation entries in the same flat format
 * used internally by Translation::collectLocaleTranslations().
 * The bundled source is merged additively with app lang_dir files during sync.
 *
 * Bind a custom implementation in a ServiceProvider to override the default
 * BundledTranslationSource, or extract translations into a satellite package
 * (e.g. rivalex/lingua-translations) without touching the core loader.
 */
interface BaseTranslationSource
{
    /**
     * Return locale codes for which base translations are available.
     *
     * @return array<int, string>
     */
    public function available(): array;

    /**
     * Return flat translation entries for the given locale.
     *
     * Each entry must have the shape:
     *   [ 'locale', 'group', 'key', 'value', 'is_vendor', 'vendor' ]
     *
     * @return array<int, array{locale: string, group: string, key: string, value: string, is_vendor: bool, vendor: string|null}>
     */
    public function translationsFor(string $locale): array;
}
