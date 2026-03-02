<?php

namespace Rivalex\Lingua\Tests;

use BladeUI\Icons\BladeIconsServiceProvider;
use Flux\FluxServiceProvider;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Route;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use OutheBox\BladeFlags\BladeFlagsServiceProvider;
use Rivalex\Lingua\LinguaServiceProvider;
use Spatie\TranslationLoader\TranslationServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            FluxServiceProvider::class,
            BladeIconsServiceProvider::class,
            BladeFlagsServiceProvider::class,
            TranslationServiceProvider::class,
            \LaravelLang\Locales\ServiceProvider::class,
            \LaravelLang\Config\ServiceProvider::class,
            \LaravelLang\Publisher\ServiceProvider::class,
            LinguaServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        include_once __DIR__.'/../database/migrations/create_lingua_table.php.stub';
        (new \CreateLinguaTable)->up();
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Lingua' => \Rivalex\Lingua\Facades\Lingua::class,
        ];
    }

    public function defineEnvironment($app): void
    {
        tap($app->make('config'), function (Repository $config) {
            $config->set('app.key', 'base64:6Cu69K6S7N25Lp8YV780m3W5vUv7R3P8w4C5o2A6B7E=');
            $config->set('database.default', 'test');
            $config->set('database.connections.test', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);
        });
    }

    protected function defineRoutes($router): void
    {
        Route::livewire('languages', 'lingua::languages')
            ->name('lingua.languages');

        Route::livewire('translations/{locale?}', 'lingua::translations')
            ->name('lingua.translations');

        Route::get('assets/{path}', function (string $path) {
            // Serve built assets directly from the package when they are not published
            $file = dirname(__DIR__)."/src/dist/{$path}";
            abort_unless(is_file($file), 404);

            $mime = match (pathinfo($file, PATHINFO_EXTENSION)) {
                'css' => 'text/css',
                'js' => 'application/javascript',
                'map' => 'application/json',
                default => null,
            };

            return response()->file($file, array_filter([
                'Content-Type' => $mime,
                'Cache-Control' => 'public, max-age=31536000',
            ]));
        })->where('path', '.*')->name('lingua.assets');
    }
}
