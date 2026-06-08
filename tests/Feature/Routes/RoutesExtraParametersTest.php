<?php

declare(strict_types=1);

it('has routes_extra_parameters config set to null by default', function () {
    expect(config('lingua.routes_extra_parameters'))->toBeNull();
});

it('computes empty suffix when routes_extra_parameters is null', function () {
    config(['lingua.routes_extra_parameters' => null]);

    $extra = config('lingua.routes_extra_parameters');
    $suffix = $extra ? '/'.ltrim((string) $extra, '/') : '';

    expect($suffix)->toBe('');
});

it('computes correct suffix from a plain optional segment', function () {
    config(['lingua.routes_extra_parameters' => '{team?}']);

    $extra = config('lingua.routes_extra_parameters');
    $suffix = $extra ? '/'.ltrim((string) $extra, '/') : '';

    expect($suffix)->toBe('/{team?}');
});

it('strips a leading slash from routes_extra_parameters', function () {
    config(['lingua.routes_extra_parameters' => '/{tenant?}']);

    $extra = config('lingua.routes_extra_parameters');
    $suffix = $extra ? '/'.ltrim((string) $extra, '/') : '';

    expect($suffix)->toBe('/{tenant?}');
});

it('supports chained optional segments', function () {
    config(['lingua.routes_extra_parameters' => '{org?}/{team?}']);

    $extra = config('lingua.routes_extra_parameters');
    $suffix = $extra ? '/'.ltrim((string) $extra, '/') : '';

    expect($suffix)->toBe('/{org?}/{team?}');
});

it('lingua named routes are registered when no extra parameters are configured', function () {
    expect(app('router')->has('lingua.languages'))->toBeTrue()
        ->and(app('router')->has('lingua.translations'))->toBeTrue()
        ->and(app('router')->has('lingua.statistics'))->toBeTrue()
        ->and(app('router')->has('lingua.settings'))->toBeTrue();
});
