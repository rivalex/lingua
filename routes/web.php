<?php

use Illuminate\Support\Facades\Route;

$middleware = array_unique(array_merge(['web'], config('lingua.middleware', 'web')));

$extraParams = config('lingua.routes_extra_parameters');
$suffix = $extraParams ? '/'.ltrim((string) $extraParams, '/') : '';

Route::group([
    'middleware' => $middleware,
    'prefix' => config('lingua.routes_prefix', 'lingua'),
], function () use ($suffix) {
    Route::livewire('languages'.$suffix, 'lingua::languages')
        ->name('lingua.languages');

    Route::livewire('translations/{locale?}'.$suffix, 'lingua::translations')
        ->name('lingua.translations');

    Route::livewire('statistics'.$suffix, 'lingua::statistics')
        ->name('lingua.statistics');

    Route::livewire('settings'.$suffix, 'lingua::settings')
        ->name('lingua.settings');

    Route::get('assets/{path}', function (string $path) {
        // Serve built assets directly from the package when they are not published.
        // Realpath jail: prevent path traversal outside src/dist/.
        $base = realpath(dirname(__DIR__).'/src/dist');
        $file = realpath(dirname(__DIR__).'/src/dist/'.$path);

        abort_unless(
            $base !== false &&
            $file !== false &&
            str_starts_with($file, $base.DIRECTORY_SEPARATOR) &&
            is_file($file),
            404
        );

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
    })->where('path', '.+')->name('lingua.assets');
});
