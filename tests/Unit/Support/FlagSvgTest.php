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
