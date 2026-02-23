<?php

namespace Rivalex\Lingua\TranslationManager;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Spatie\TranslationLoader\LanguageLine;
use Spatie\TranslationLoader\TranslationLoaderManager;
use Spatie\TranslationLoader\TranslationLoaders\TranslationLoader;

class LinguaManager extends TranslationLoaderManager {
    /**
     * Load the messages for the given locale.
     *
     * @param  string  $locale
     * @param  string  $group
     * @param  string  $namespace
     *
     * @return array
     */
    public function load($locale, $group, $namespace = null): array
    {
        try {
            $fileTranslations = parent::load($locale, $group, $namespace);

            if (! is_null($namespace) && $namespace !== '*') {
                return $fileTranslations;
            }

            $loaderTranslations = $this->getTranslationsForTranslationLoaders($locale, $group, $namespace);

            return array_replace_recursive($fileTranslations, $loaderTranslations);
        } catch (QueryException $exception) {
            $modelClass = config('lingua.model');
            $model = new $modelClass();

            if (is_a($model, LanguageLine::class) && ! Schema::hasTable($model->getTable())) {
                return parent::load($locale, $group, $namespace);
            }

            throw $exception;
        }
    }

    protected function getTranslationsForTranslationLoaders(
        string $locale,
        string $group,
        string|null $namespace = null
    ): array {
        return collect(config('lingua.translation_loaders'))
            ->map(function (string $className) {
                return app($className);
            })
            ->mapWithKeys(function (TranslationLoader $translationLoader) use ($locale, $group, $namespace) {
                return $translationLoader->loadTranslations($locale, $group, $namespace);
            })
            ->toArray();
    }

}
