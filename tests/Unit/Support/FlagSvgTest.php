<?php

declare(strict_types=1);

use Rivalex\Lingua\Support\FlagSvg;

$codes = array_keys(require __DIR__.'/../../../resources/data/locales.php');

it('resolves every registry locale to an existing flag file', function (string $code): void {
    foreach ([false, true] as $circle) {
        expect(FlagSvg::path($code, $circle))->toBeFile()
            ->and(FlagSvg::html($code, $circle))->toStartWith('<svg');
    }
})->with($codes);

it('shows a real flag for every registry locale except the documented neutral ones', function (): void {
    $neutral = static fn (bool $circle): array => array_values(array_filter(
        array_keys(require __DIR__.'/../../../resources/data/locales.php'),
        static fn (string $code): bool => str_ends_with(FlagSvg::path($code, $circle), 'country-xx.svg'),
    ));

    expect($neutral(false))->toBe(['ckb', 'eo', 'tt', 'ug'])
        ->and($neutral(true))->toBe(['ckb', 'tt', 'ug']);
});

it('falls back to the country flag of the main language area', function (): void {
    expect(basename(FlagSvg::path('gd')))->toBe('country-gb-sct.svg')
        ->and(basename(FlagSvg::path('mni_Mtei')))->toBe('country-in.svg')
        ->and(basename(FlagSvg::path('haw', true)))->toBe('circle-country-us-hi.svg')
        ->and(basename(FlagSvg::path('haw')))->toBe('language-haw.svg');
});

it('matches regional codes case-insensitively and falls back to the base language', function (): void {
    expect(basename(FlagSvg::path('de_AT')))->toBe('language-de-at.svg')
        ->and(basename(FlagSvg::path('de-AT', true)))->toBe('circle-language-de-at.svg')
        ->and(basename(FlagSvg::path('sr_Cyrl')))->toBe('language-sr.svg');
});

it('uses the neutral flag for an unknown code', function (): void {
    expect(basename(FlagSvg::path('zz')))->toBe('country-xx.svg')
        ->and(basename(FlagSvg::path('', true)))->toBe('circle-country-xx.svg');
});

it('adds the class to the svg element, escaped', function (): void {
    expect(FlagSvg::html('it', false, 'h-6 w-6'))->toStartWith('<svg class="h-6 w-6"')
        ->and(FlagSvg::html('it', false, '"><script>'))->not->toContain('<script>');
});

it('ships the attribution notice with the flags', function (): void {
    expect(file_get_contents(__DIR__.'/../../../resources/flags/NOTICE.md'))
        ->toContain('CC-BY 4.0')
        ->toContain('circle-flags');
});
