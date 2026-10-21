<?php

declare(strict_types=1);

use Rivalex\Lingua\Enums\LinguaType;

it('returns the correct description for every case', function (LinguaType $type, string $expected): void {
    expect($type->description())->toBe($expected);
})->with([
    [LinguaType::any,      'Any type of translation'],
    [LinguaType::text,     'Simple Text with no formatting'],
    [LinguaType::html,     'HTML with formatting'],
    [LinguaType::markdown, 'Markdown with formatting'],
]);

it('labelWithIcon contains the label text', function (LinguaType $type): void {
    expect($type->labelWithIcon())->toContain($type->label());
})->with(LinguaType::cases());

it('labelWithIcon contains a div wrapper', function (LinguaType $type): void {
    expect($type->labelWithIcon())->toContain('<div');
})->with(LinguaType::cases());

it('selectValues excludes the "any" case', function (): void {
    $values = LinguaType::selectValues();

    expect(array_column($values, 'value'))->not->toContain('any');
});

it('selectValues returns three entries (text, html, markdown)', function (): void {
    expect(LinguaType::selectValues())->toHaveCount(3);
});

it('selectValues entries have both value and label keys', function (): void {
    foreach (LinguaType::selectValues() as $item) {
        expect($item)->toHaveKey('value')->toHaveKey('label');
    }
});
