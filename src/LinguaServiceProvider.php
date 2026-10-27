<?php

declare(strict_types=1);

namespace Rivalex\Lingua;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Rivalex\Lingua\Commands\AddLangCommand;
use Rivalex\Lingua\Commands\InstallCommand;
use Rivalex\Lingua\Commands\RemoveLangCommand;
use Rivalex\Lingua\Commands\SetStorageDriverCommand;
use Rivalex\Lingua\Commands\SyncToDatabaseCommand;
use Rivalex\Lingua\Commands\SyncToLocalCommand;
use Rivalex\Lingua\Commands\UninstallCommand;
use Rivalex\Lingua\Commands\UpdateLangCommand;
use Rivalex\Lingua\Contracts\BaseTranslationSource;
use Rivalex\Lingua\Contracts\TranslationRepository;
use Rivalex\Lingua\Database\DatabaseRepository;
use Rivalex\Lingua\Http\Middleware\LinguaMiddleware;
use Rivalex\Lingua\Locales\BundledTranslationSource;
use Rivalex\Lingua\Locales\LocaleRegistry;
use Rivalex\Lingua\Locales\NotificationProjector;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Services\ExtensionRegistry;
use Rivalex\Lingua\Storage\FileRepository;
use Rivalex\Lingua\Support\AtomicFileWriter;
use Rivalex\Lingua\Support\CacheCircuit;
use Rivalex\Lingua\Support\MigrationPublisher;
use Rivalex\Lingua\Support\RequestMemo;
use Rivalex\Lingua\Support\TableProbe;
use Rivalex\Lingua\Support\TranslationFileReader;
use Rivalex\Lingua\TranslationManager\LinguaManager;

class LinguaServiceProvider extends ServiceProvider
{
    /**
     * Register the extension registry singleton.
     *
     * Placed in register() so the singleton is available before any
     * extension's ServiceProvider calls $this->app->tag() in their own
     * register(). The registry itself resolves tagged implementations
     * lazily (on first call to all()), so tag order does not matter.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/lingua.php', 'lingua');

        $this->app->singleton(ExtensionRegistry::class);

        $this->app->singleton(LocaleRegistry::class, function () {
            return new LocaleRegistry(
                dataPath: __DIR__.'/../resources/data/locales.php',
            );
        });

        $this->app->bind(BaseTranslationSource::class, function ($app) {
            return new BundledTranslationSource(
                basePath: config('lingua.base_translations_path')
                    ?? __DIR__.'/../resources/translations',
            );
        });

        $this->app->singleton(TranslationFileReader::class);

        $this->app->scoped(CacheCircuit::class);

        $this->app->scoped(TableProbe::class);

        $this->app->scoped(RequestMemo::class);

        $this->app->bind(AtomicFileWriter::class);

        $this->app->bind(NotificationProjector::class, function ($app) {
            return new NotificationProjector(
                notificationsPath: config('lingua.base_notifications_path')
                    ?? __DIR__.'/../resources/notifications',
                langPath: config('lingua.lang_dir', lang_path()),
                writer: $app->make(AtomicFileWriter::class),
            );
        });

        $this->app->bind(TranslationRepository::class, function ($app) {
            $driver = config('lingua.storage.driver', 'database');

            if ($driver === 'file') {
                return new FileRepository(
                    writer: $app->make(AtomicFileWriter::class),
                    reader: $app->make(TranslationFileReader::class),
                    langPath: config('lingua.lang_dir', lang_path()),
                    bundled: $app->make(BaseTranslationSource::class),
                );
            }

            return new DatabaseRepository(
                bundled: $app->make(BaseTranslationSource::class),
            );
        });

        $this->registerLoader();

        $this->registerTranslator();
    }

    /**
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->bootPackageResources();

        /* Register Blade Namespace components */
        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'lingua');

        Livewire::addNamespace(
            namespace: 'lingua',
            classNamespace: 'Rivalex\\Lingua\\Livewire',
            classPath: __DIR__.'/Livewire',
            classViewPath: $this->getViewPath(),
        );

        // Share the extension registry with all lingua views so that
        // @foreach loops in page views can render extension-provided
        // Livewire components without an explicit app() call in Blade.
        View::share('linguaExtensions', $this->app->make(ExtensionRegistry::class));

        $this->app->make(Kernel::class)->appendMiddlewareToGroup('web', LinguaMiddleware::class);

        $this->registerDefaultGate();

        if (linguaIsFileMode()) {
            Log::notice('[Lingua] Running in file-mode (LINGUA_STORAGE_DRIVER=file). Database sync commands are still available via lingua:sync-to-database.');
        }
    }

    /**
     * Commands, publishable resources, routes, translations and views, in the
     * order and with the publish tags the package used under
     * spatie/laravel-package-tools, so published hosts see no difference.
     */
    protected function bootPackageResources(): void
    {
        $this->commands([
            AddLangCommand::class,
            RemoveLangCommand::class,
            UpdateLangCommand::class,
            SyncToLocalCommand::class,
            SyncToDatabaseCommand::class,
            SetStorageDriverCommand::class,
            UninstallCommand::class,
            InstallCommand::class,
        ]);

        $translations = __DIR__.'/../resources/lang';
        $appTranslations = lang_path('vendor/lingua');
        $views = realpath(__DIR__.'/../resources/views') ?: __DIR__.'/../resources/views';

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/lingua.php' => config_path('lingua.php')], 'lingua-config');

            $migrations = [];
            foreach (['create_language_lines_table', 'create_languages_table', 'create_lingua_settings_table'] as $i => $basename) {
                $migrations[__DIR__."/../database/migrations/{$basename}.php"] = MigrationPublisher::targetPath($basename, $i);
            }
            $this->publishes($migrations, 'lingua-migrations');
        }

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        $this->loadTranslationsFrom($translations, 'lingua');
        $this->loadJsonTranslationsFrom($translations);
        $this->loadJsonTranslationsFrom($appTranslations);

        $this->loadViewsFrom($views, 'lingua');

        if ($this->app->runningInConsole()) {
            $this->publishes([$translations => $appTranslations], 'lingua-translations');
            $this->publishes([$views => base_path('resources/views/vendor/lingua')], 'lingua-views');
        }
    }

    /**
     * Deny by default: when the host does not define the configured ability, allow
     * it only in the local environment. Package providers boot before the host's
     * providers, so a host Gate::define() replaces this fallback.
     */
    protected function registerDefaultGate(): void
    {
        $gate = Lingua::gate();

        if ($gate !== null && ! Gate::has($gate)) {
            Gate::define($gate, fn ($user): bool => $this->app->environment('local'));
        }
    }

    protected function registerLoader(): void
    {
        $this->app->extend('translation.loader', function ($original, $app) {
            $class = config('lingua.translation_manager');
            $langPath = config('lingua.lang_dir', lang_path());

            if (! $class || ! class_exists($class)) {
                return new LinguaManager($app['files'], $langPath);
            }

            return new $class($app['files'], $langPath);
        });
    }

    /**
     * Set the fallback locale to the default language. Like the loader, this wraps the
     * binding with extend() inside register(): a singleton() set in boot()
     * is overwritten by Laravel's deferred translation provider.
     */
    protected function registerTranslator(): void
    {
        $this->app->extend('translator', function ($translator) {
            try {
                $code = Language::default()?->code;

                if (is_string($code) && $code !== '') {
                    $translator->setFallback($code);
                }
            } catch (\Throwable) {
                // No table yet, or no database: keep the framework fallback.
            }

            return $translator;
        });
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return ['translator', 'translation.loader', ExtensionRegistry::class];
    }

    protected function getViewPath(): string
    {
        $publishedPath = resource_path('views/vendor/lingua');
        $packagePath = __DIR__.'/../resources/views';

        return file_exists($publishedPath) ? $publishedPath : $packagePath;
    }
}
