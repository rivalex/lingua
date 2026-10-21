<?php

namespace Rivalex\Lingua\Database;

use Spatie\TranslationLoader\Exceptions\InvalidConfiguration;
use Spatie\TranslationLoader\LanguageLine;

class Db extends \Spatie\TranslationLoader\TranslationLoaders\Db
{
    /**
     * @throws InvalidConfiguration
     */
    protected function getConfiguredModelClass(): string
    {
        $modelClass = config('lingua.model');

        if (! is_a(new $modelClass, LanguageLine::class)) {
            throw InvalidConfiguration::invalidModel($modelClass);
        }

        return $modelClass;
    }
}
