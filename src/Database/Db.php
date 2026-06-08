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
        if ($namespace !== null && $namespace !== '*') {
            return [];
        }

        $modelClass = config('lingua.model');

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
