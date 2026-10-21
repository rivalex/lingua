<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Tests;

use BladeUI\Icons\BladeIconsServiceProvider;
use Flux\FluxServiceProvider;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Route;
use Livewire\LivewireServiceProvider;
use OutheBox\BladeFlags\BladeFlagsServiceProvider;
use Rivalex\Lingua\Database\Seeders\LinguaSeeder;
use Rivalex\Lingua\Facades\Lingua;
use Rivalex\Lingua\Http\Controllers\TransferExportController;
use Rivalex\Lingua\LinguaServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            FluxServiceProvider::class,
            BladeIconsServiceProvider::class,
            BladeFlagsServiceProvider::class,
            LinguaServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->artisan('migrate'); // Explicitly run migrations
        $this->seed(LinguaSeeder::class);
        // Reset fallback locale to avoid leakage from LinguaMiddleware which calls
        // app()->setFallbackLocale() as a side effect, causing order-dependent failures.
        app()->setFallbackLocale(config('app.fallback_locale', 'en'));
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Lingua' => Lingua::class,
        ];
    }

    public function defineEnvironment($app): void
    {
        $langPath = __DIR__.'/tmp/lang';

        // Wipe non-default locale entries (e.g. it.json written by file-mode tests)
        // before each test so the seeder never discovers stale locales and creates
        // unwanted Language records. Default locale ('en') files are preserved.
        if (is_dir($langPath)) {
            foreach (glob($langPath.'/*') ?: [] as $entry) {
                $base = basename($entry);
                if ($base === 'en' || $base === 'en.json' || str_starts_with($base, '.')) {
                    continue;
                }
                if (is_file($entry)) {
                    unlink($entry);
                } elseif (is_dir($entry)) {
                    $sub = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($entry, \RecursiveDirectoryIterator::SKIP_DOTS),
                        \RecursiveIteratorIterator::CHILD_FIRST
                    );
                    foreach ($sub as $subEntry) {
                        $subEntry->isDir() ? rmdir((string) $subEntry) : unlink((string) $subEntry);
                    }
                    rmdir($entry);
                }
            }
        }

        if (! is_dir($langPath)) {
            mkdir($langPath, 0777, true);
        }

        // Copy bundled 'en' translations into the test lang dir so the seeder
        // always creates a stable set of Translation rows regardless of prior test state.
        $bundledEn = realpath(__DIR__.'/../resources/translations/en');
        if ($bundledEn && is_dir($bundledEn)) {
            $targetEn = $langPath.'/en';
            if (! is_dir($targetEn)) {
                mkdir($targetEn, 0777, true);
            }
            foreach (glob($bundledEn.'/*.php') ?: [] as $src) {
                copy($src, $targetEn.'/'.basename($src));
            }
        }

        $app->useLangPath($langPath);

        // Isolate tests from the real bundled dataset (resources/translations):
        // otherwise every seeder run would import the full 26-locale catalogue,
        // making counts non-deterministic and the suite drastically slower.
        // Dedicated bundled-sync tests point this config at their own fixtures.
        $bundledPath = __DIR__.'/tmp/bundled';
        if (! is_dir($bundledPath)) {
            mkdir($bundledPath, 0777, true);
        }

        tap($app->make('config'), function (Repository $config) use ($langPath, $bundledPath) {
            $config->set('lingua.lang_dir', $langPath);
            $config->set('lingua.base_translations_path', $bundledPath);
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

        Route::livewire('statistics', 'lingua::statistics')
            ->name('lingua.statistics');

        Route::livewire('settings', 'lingua::settings')
            ->name('lingua.settings');

        Route::livewire('transfer', 'lingua::transfer')
            ->name('lingua.transfer');

        Route::get('transfer/export', [TransferExportController::class, 'download'])
            ->name('lingua.transfer.export');

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
