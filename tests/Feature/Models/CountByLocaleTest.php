<?php

declare(strict_types=1);

use Rivalex\Lingua\Models\Translation;

function makeTranslation(array $text): Translation
{
    static $i = 0;
    $i++;

    return Translation::create([
        'group' => 'count_test',
        'key' => 'key_'.$i.'_'.uniqid(),
        'text' => $text,
        'is_vendor' => false,
        'vendor' => null,
    ]);
}

test('countByLocale returns correct count on SQLite without SQL JSON functions', function (): void {
    $locale = 'fr_cnt_'.uniqid();

    makeTranslation(['en' => 'Hello', $locale => 'Bonjour']);
    makeTranslation(['en' => 'World', $locale => 'Monde']);
    makeTranslation(['en' => 'Only English']);

    expect(Translation::countByLocale($locale))->toBe(2);
});

test('countByLocale returns 0 for locale with no translations', function (): void {
    expect(Translation::countByLocale('xx_NONEXISTENT_'.uniqid()))->toBe(0);
});

test('getLocaleStats returns correct total, translated, missing, percentage', function (): void {
    $locale = 'zz_stats_'.uniqid();

    makeTranslation(['en' => 'A', $locale => 'A-zz']);
    makeTranslation(['en' => 'B', $locale => 'B-zz']);

    $total = Translation::count();
    $stats = Translation::getLocaleStats($locale);

    expect($stats)->toHaveKeys(['total', 'translated', 'missing', 'percentage'])
        ->and($stats['translated'])->toBe(2)
        ->and($stats['total'])->toBe($total)
        ->and($stats['missing'])->toBe($total - 2)
        ->and($stats['percentage'])->toBe(round(2 / $total * 100, 2));
});
