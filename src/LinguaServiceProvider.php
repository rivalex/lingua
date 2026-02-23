<?php

namespace Rivalex\Lingua;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Translation\Translator;
use Livewire\Livewire;
use Rivalex\Lingua\Commands\SyncToDatabaseCommand;
use Rivalex\Lingua\Commands\SyncToLocalCommand;
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
            ->hasCommands(SyncToLocalCommand::class, SyncToDatabaseCommand::class)
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->startWith(function(InstallCommand $command) {
                        $command->info('Hello, and welcome to Lingua new package!');
                    })
                    ->publishAssets()
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('rivalex/lingua')
                    ->endWith(function(InstallCommand $command) {
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
        Blade::componentNamespace('Rivalex\\Views\\Components', 'lingua');

        /* Add Livewire Namespace for components */
        Livewire::addNamespace('lingua', $this->getViewPath());

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

    /**
     * @return void
     */
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
     *
     * @return array
     */
    public function provides(): array
    {
        return ['translator', 'translation.loader'];
    }

    protected function getViewPath(): string
    {
        $publishedPath = resource_path("views/vendor/rivalex/lingua");
        $packagePath = __DIR__ . "/../resources/views/lingua";

        return file_exists($publishedPath) ? $publishedPath : $packagePath;
    }

    protected function registerLivewireComponent(string $name, string $fileName): void
    {
        $publishedPath = resource_path("views/vendor/rivalex/lingua/{$fileName}");
        $packagePath = __DIR__ . "/../resources/views/lingua/{$fileName}";

        $componentPath = file_exists($publishedPath) ? $publishedPath : $packagePath;

        Livewire::addComponent($name, $componentPath);
    }

//    public function register(): void
//    {
//        parent::register();
//
//        $this->app->singleton(Lingua::class, function () {
//           return new Lingua();
//        });
//    }
}
