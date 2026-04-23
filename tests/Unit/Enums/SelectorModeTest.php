<?php

declare(strict_types=1);

use Rivalex\Lingua\Enums\SelectorMode;

it('returns the correct description for every case', function (SelectorMode $mode, string $expected): void {
    expect($mode->description())->toBe($expected);
})->with([
    [SelectorMode::Sidebar,  'Opens in a slide-over sidebar panel'],
    [SelectorMode::Modal,    'Opens in a centred modal dialog'],
    [SelectorMode::Dropdown, 'Opens as an inline dropdown menu'],
    [SelectorMode::Headless, 'No built-in UI — render manually in your layout'],
]);

it('selectValues returns one entry per case', function (): void {
    expect(SelectorMode::selectValues())->toHaveCount(4);
});

it('selectValues entries have value and label keys', function (): void {
    foreach (SelectorMode::selectValues() as $item) {
        expect($item)->toHaveKey('value')->toHaveKey('label');
    }
});

it('selectValues values match enum case values', function (): void {
    $expected = array_column(SelectorMode::cases(), 'value');
    $actual = array_column(SelectorMode::selectValues(), 'value');

    expect($actual)->toBe($expected);
});
