<?php

declare(strict_types=1);

namespace Rivalex\Lingua\TranslationManager;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Translation\FileLoader;
use Rivalex\Lingua\Contracts\TranslationLoader;
use Rivalex\Lingua\Contracts\TranslationRepository;
use Rivalex\Lingua\Database\DatabaseRepository;
use Rivalex\Lingua\Database\Db;

/**
 * Extends FileLoader to merge file-based translations with database-backed ones.
 *
 * Registered as `translation.loader` in LinguaServiceProvider so that
 * Laravel's Translator calls load() on every translation lookup.
 */
final class LinguaManager extends FileLoader
{
    /**
     * Load the messages for the given locale.
     *
     * In database mode, vendor namespaces are served from the DB (cached, merged
     * over files). In file mode the file result is returned unchanged, exactly
     * as before this change.
     */
    public function load($locale, $group, $namespace = null): array
    {
        try {
            $fileTranslations = parent::load($locale, $group, $namespace);

            if (! is_null($namespace) && $namespace !== '*') {
                if (app(TranslationRepository::class) instanceof DatabaseRepository) {
                    return array_replace_recursive(
                        $fileTranslations,
                        app(Db::class)->loadTranslations($locale, $group, $namespace)
                    );
                }

                return $fileTranslations;
            }

            $loaderTranslations = $this->getTranslationsForTranslationLoaders($locale, $group, $namespace);

            return array_replace_recursive($fileTranslations, $loaderTranslations);
        } catch (QueryException $exception) {
            $modelClass = config('lingua.model');

            if (
                is_string($modelClass) &&
                class_exists($modelClass) &&
                method_exists($modelClass, 'getTable') &&
                ! Schema::hasTable((new $modelClass)->getTable())
            ) {
                return parent::load($locale, $group, $namespace);
            }

            throw $exception;
        }
    }

    protected function getTranslationsForTranslationLoaders(
        string $locale,
        string $group,
        ?string $namespace = null
    ): array {
        return collect(config('lingua.translation_loaders'))
            ->map(fn (string $className) => app($className))
            ->mapWithKeys(function (TranslationLoader $loader) use ($locale, $group, $namespace) {
                return $loader->loadTranslations($locale, $group, $namespace);
            })
            ->toArray();
    }
}
