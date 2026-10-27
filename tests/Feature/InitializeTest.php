<?php

use Illuminate\Support\Facades\Schema;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

it('can access the `app configuration`', function () {
    expect(config('lingua'))->toBeArray()
        ->and(config('lingua.routes_prefix'))->toBe('lingua');
});

it('has `migrated` all the package tables', function () {
    expect(Schema::hasTable('languages'))->toBeTrue()
        ->and(Schema::hasTable('language_lines'))->toBeTrue();
});

it('can `seed` the default `language`', function () {
    expect(Language::where('code', config('app.fallback_locale', 'en'))->exists())->toBeTrue();
});

it('can `seed` the default `translations`', function () {
    expect(Translation::count())->toBeGreaterThan(0);
});
