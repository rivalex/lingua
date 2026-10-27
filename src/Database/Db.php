<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Database;

use Illuminate\Database\Eloquent\Model;
use Rivalex\Lingua\Contracts\TranslationLoader;
use Rivalex\Lingua\Exceptions\InvalidConfiguration;

final class Db implements TranslationLoader
{
    /**
     * @return array<string, mixed>
     *
     * @throws InvalidConfiguration
     */
    public function loadTranslations(string $locale, string $group, ?string $namespace = null): array
    {
        // File mode never reads the table at runtime.
        if (linguaIsFileMode()) {
            return [];
        }

        // The JSON strings (group '*', namespace '*') live in the 'single' rows.
        if ($group === '*' && ($namespace === '*' || $namespace === null)) {
            $group = 'single';
        }

        $modelClass = config('lingua.model');

        if ($namespace !== null && $namespace !== '*') {
            if (
                ! is_string($modelClass) ||
                ! is_subclass_of($modelClass, Model::class) ||
                ! method_exists($modelClass, 'getVendorTranslationsForGroup')
            ) {
                throw InvalidConfiguration::invalidModel((string) $modelClass);
            }

            return $modelClass::getVendorTranslationsForGroup($locale, $namespace, $group);
        }

        if (
            ! is_string($modelClass) ||
            ! is_subclass_of($modelClass, Model::class) ||
            ! method_exists($modelClass, 'getTranslationsForGroup')
        ) {
            throw InvalidConfiguration::invalidModel((string) $modelClass);
        }

        return $modelClass::getTranslationsForGroup($locale, $group);
    }
}
