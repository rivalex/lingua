<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Contracts;

interface TranslationLoader
{
    /**
     * Load translations for the given locale, group, and optional namespace.
     *
     * @return array<string, mixed>
     */
    public function loadTranslations(string $locale, string $group, ?string $namespace = null): array;
}
