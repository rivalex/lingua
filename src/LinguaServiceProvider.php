<?php

declare(strict_types=1);

namespace Rivalex\Lingua;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Translation\Translator;
use Livewire\Livewire;
use Rivalex\Lingua\Commands\AddLangCommand;
use Rivalex\Lingua\Commands\RemoveLangCommand;
use Rivalex\Lingua\Commands\SetStorageDriverCommand;
use Rivalex\Lingua\Commands\SyncToDatabaseCommand;
use Rivalex\Lingua\Commands\SyncToLocalCommand;
use Rivalex\Lingua\Commands\UpdateLangCommand;
use Rivalex\Lingua\Contracts\BaseTranslationSource;
use Rivalex\Lingua\Contracts\TranslationRepository;
use Rivalex\Lingua\Database\DatabaseRepository;
use Rivalex\Lingua\Database\Seeders\LinguaSeeder;
use Rivalex\Lingua\Http\Middleware\LinguaMiddleware;
use Rivalex\Lingua\Locales\BundledTranslationSource;
use Rivalex\Lingua\Locales\LocaleRegistry;
use Rivalex\Lingua\Locales\NotificationProjector;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Services\ExtensionRegistry;
use Rivalex\Lingua\Storage\FileRepository;
use Rivalex\Lingua\Support\AtomicFileWriter;
use Rivalex\Lingua\Support\TranslationFileReader;
use Rivalex\Lingua\TranslationManager\LinguaManager;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LinguaServiceProvider extends PackageServiceProvider
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
        parent::register();

        $this->app->singleton(ExtensionRegistry::class);

        $this->app->singleton(LocaleRegistry::class, function () {
            return new LocaleRegistry(
                dataPath: __DIR__.'/../resources/data/locales.php',
            );
        });

        $this->app->bind(BaseTranslationSource::class, function ($app) {
            return new BundledTranslationSource(
                basePath: config('lingua.base_translations_path',
                    __DIR__.'/../resources/translations'),
            );
        });

        $this->app->singleton(TranslationFileReader::class);

        $this->app->bind(AtomicFileWriter::class);

        $this->app->bind(NotificationProjector::class, function ($app) {
            return new NotificationProjector(
                notificationsPath: config('lingua.base_notifications_path',
                    __DIR__.'/../resources/notifications'),
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

            return new DatabaseRepository;
        });

        $this->registerLoader();
    }

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('lingua')
            ->hasConfigFile()
            ->hasViews('lingua')
            ->hasTranslations()
            ->hasRoute('web')
            ->hasMigration('create_language_lines_table')
            ->hasMigration('create_languages_table')
            ->hasMigration('create_lingua_settings_table')
            ->hasCommands(
                AddLangCommand::class,
                RemoveLangCommand::class,
                UpdateLangCommand::class,
                SyncToLocalCommand::class,
                SyncToDatabaseCommand::class,
                SetStorageDriverCommand::class,
            )
            ->hasInstallCommand(function (InstallCommand $command) {
                $driver = 'database';

                $command
                    ->startWith(function (InstallCommand $command) use (&$driver) {
                        $command->info('Hello, and welcome to Lingua new package!');
                        $command->info('Starting the installation process...');

                        $driver = $command->choice('Translation storage driver?', ['database', 'file'], 0);

                        $command->info("Set LINGUA_STORAGE_DRIVER={$driver} in your .env, then run 'php artisan config:clear'.");

                        if ($driver === 'file') {
                            $command->warn('FILE DRIVER: translations are written directly to lang/.');
                            $command->warn('Your deploy pipeline (Forge/Envoyer/CI) may overwrite these files');
                            $command->warn('or fail on a dirty working tree. Commit lang/ changes deliberately.');
                            $command->warn('See docs: Storage Drivers > File mode caveats.');
                        }
                    })
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('rivalex/lingua')
                    ->endWith(function (InstallCommand $command) use (&$driver) {
                        $tablesCreated = Schema::hasTable('languages') && Schema::hasTable('language_lines');
                        if ($tablesCreated) {
                            $command->info('Installing Lingua package...');
                            if ($driver === 'database') {
                                $command->call('db:seed', ['--class' => LinguaSeeder::class]);
                            } elseif ($driver === 'file') {
                                try {
                                    Lingua::installDefaultLanguage();
                                    $command->info('Default language installed and lang/ files seeded.');
                                } catch (\Throwable $e) {
                                    $command->warn('Could not seed default language: '.$e->getMessage());
                                }
                            }
                            $command->info('Lingua package installed successfully!');
                        } else {
                            $command->info('Lingua package installed successfully!');
                            $command->info('Please run "php artisan migrate" to create the database tables.');
                            if ($driver === 'database') {
                                $command->info('Please run "php artisan db:seed" to populate the database with default data.');
                            } elseif ($driver === 'file') {
                                $command->info('After migrating, run "php artisan lingua:storage file" to seed default lang/ files.');
                            }
                        }
                    });
            });
    }

    /**
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        parent::boot();

        /* Register Blade Namespace components */
        Blade::anonymousComponentPath(__DIR__.'/Views/Components', 'lingua');

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

        $this->registerTranslator();

        if (linguaIsFileMode()) {
            Log::notice('[Lingua] Running in file-mode (LINGUA_STORAGE_DRIVER=file). Database sync commands are still available via lingua:sync-to-database.');
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

    protected function registerTranslator(): void
    {
        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];

            try {
                $defaultLocale = Language::default()?->code ?? config('app.locale');
            } catch (\Throwable) {
                $defaultLocale = config('app.locale');
            }

            // When registering the translator component, we'll need to set the default
            // locale as well as the fallback locale. So, we'll grab the application
            // configuration so we can easily get both of these values from there.
            $locale = $app->getLocale();
            $trans = new Translator($loader, $locale);
            $trans->setFallback($defaultLocale);

            return $trans;
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
