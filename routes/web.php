<?php

use Illuminate\Support\Facades\Route;

$middleware = array_unique(array_merge(['web'], config('lingua.middleware', 'web') ));

Route::group([
    'middleware' => $middleware,
    'prefix' => config('lingua.routes_prefix', 'lingua'),
], function () {
    Route::livewire('languages', 'lingua::languages')
         ->name('lingua.languages');

    Route::livewire('translations/{locale?}', 'lingua::translations')
         ->name('lingua.translations');

    Route::get('assets/{path}', function (string $path) {
        // Serve built assets directly from the package when they are not published
        $file = dirname(__DIR__) . "/src/dist/{$path}";
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
});
