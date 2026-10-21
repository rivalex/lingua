<?php

namespace Rivalex\Lingua;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Support\Facades\Blade;
use Illuminate\Translation\Translator;
use Livewire\Livewire;
use Rivalex\Lingua\Commands\AddLangCommand;
use Rivalex\Lingua\Commands\RemoveLangCommand;
use Rivalex\Lingua\Commands\SyncToDatabaseCommand;
use Rivalex\Lingua\Commands\SyncToLocalCommand;
use Rivalex\Lingua\Commands\UpdateLangCommand;
use Rivalex\Lingua\Database\Seeders\LinguaSeeder;
use Rivalex\Lingua\Http\Middleware\LinguaMiddleware;
use Rivalex\Lingua\Models\Language;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LinguaServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('lingua')
            ->hasConfigFile()
            ->hasViews('lingua')
            ->hasTranslations()
            ->hasAssets()
            ->hasRoute('web')
            ->hasMigration('create_lingua_table')
            ->hasCommands(
                AddLangCommand::class,
                RemoveLangCommand::class,
                UpdateLangCommand::class,
                SyncToLocalCommand::class,
                SyncToDatabaseCommand::class,
            )
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->startWith(function (InstallCommand $command) {
                        $command->info('Hello, and welcome to Lingua new package!');
                    })
                    ->publishAssets()
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('rivalex/lingua')
                    ->endWith(function (InstallCommand $command) {
                        $command->call('db:seed', ['--class' => LinguaSeeder::class]);
                        $command->info('Lingua package installed successfully!');
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

        /* Add Livewire Namespace for components */
        //        Livewire::addNamespace('lingua', $this->getViewPath());

        Livewire::addNamespace(
            namespace: 'lingua',
            classNamespace: 'Rivalex\\Lingua\\Livewire',
            classPath: __DIR__.'/Livewire',
            classViewPath: $this->getViewPath(),
        );

        $this->app->make(Kernel::class)->appendMiddlewareToGroup('web', LinguaMiddleware::class);

        $this->registerLoader();
        $this->registerTranslator();
    }

    protected function registerLoader(): void
    {
        $this->app->singleton('translation.loader', function ($app) {
            $class = config('lingua.translation_manager');

            return new $class($app['files'], $app['path.lang']);
        });
    }

    protected function registerTranslator(): void
    {
        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];
            $defaultLocale = Language::default()->code ?? config('app.locale');
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
        return ['translator', 'translation.loader'];
    }

    protected function getViewPath(): string
    {
        $publishedPath = resource_path('views/vendor/lingua');
        $packagePath = __DIR__.'/../resources/views';

        return file_exists($publishedPath) ? $publishedPath : $packagePath;
    }
}
