<?php

use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

beforeEach(function () {
    Language::factory()->count(1)->create();
});

it('can access the app configuration', function () {
    expect(config('lingua'))->toBeArray();
    expect(config('lingua.routes_prefix'))->toBe('lingua');
});

it('can migrate all the package tables', function () {
    expect(\Illuminate\Support\Facades\DB::table('languages')->exists())->toBeTrue();
    expect(\Illuminate\Support\Facades\DB::table('language_lines')->exists())->toBeTrue();
});

it('can seed the default language', function () {
    expect(Language::where('code', config('lingua.default_locale'))->first()->exists())->toBeTrue();
});

it('can seed the default translations', function () {
    expect(Translation::whereNotNull('text->' . config('lingua.default_locale'))->count())->toBeGreaterThan(0);
});

it('can seed extra languages', function () {
    expect(Language::whereNot('code', config('lingua.default_locale'))->count())->toBeGreaterThan(0);
});
