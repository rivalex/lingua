<?php

declare(strict_types=1);

use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

function makeStatTranslation(array $text): Translation
{
    static $i = 0;
    $i++;

    return Translation::create([
        'group' => 'stat_test',
        'key' => 'stat_key_'.$i.'_'.uniqid(),
        'text' => $text,
        'is_vendor' => false,
        'vendor' => null,
    ]);
}

test('withStatistics()->get() populates computed stats properties', function (): void {
    $languages = Language::withStatistics()->get();

    expect($languages)->not->toBeEmpty();

    foreach ($languages as $lang) {
        expect($lang->total_strings)->toBeInt()->toBeGreaterThanOrEqual(0)
            ->and($lang->translated_strings)->toBeInt()->toBeGreaterThanOrEqual(0)
            ->and($lang->missing_strings)->toBeInt()->toBeGreaterThanOrEqual(0)
            ->and($lang->completion_percentage)->toBeFloat();
    }
});

test('withStatistics()->find() populates computed stats for a single language', function (): void {
    $lang = Language::first();
    expect($lang)->not->toBeNull();

    $byFind = Language::withStatistics()->find($lang->id);

    expect($byFind->total_strings)->toBeInt()->toBeGreaterThanOrEqual(0)
        ->and($byFind->translated_strings)->toBeInt()->toBeGreaterThanOrEqual(0)
        ->and($byFind->missing_strings)->toBe($byFind->total_strings - $byFind->translated_strings)
        ->and($byFind->completion_percentage)->toBeFloat();
});

test('translated_strings + missing_strings = total_strings for every language', function (): void {
    $enLang = Language::where('code', 'en')->first();
    expect($enLang)->not->toBeNull();

    makeStatTranslation(['en' => 'Stat test '.uniqid()]);

    $enWithStats = Language::withStatistics()->find($enLang->id);

    expect($enWithStats->translated_strings + $enWithStats->missing_strings)
        ->toBe($enWithStats->total_strings);
});

test('completion_percentage is 0 for locale with no translations', function (): void {
    $locale = 'zz_cmp_'.uniqid();

    // Find or create a language for this locale
    $lang = Language::updateOrCreate(
        ['code' => $locale, 'regional' => 'ZZ'],
        [
            'type' => 'Latn',
            'name' => 'Test Stats Lang',
            'native' => 'Test',
            'direction' => 'ltr',
            'is_default' => false,
        ]
    );

    $withStats = Language::withStatistics()->find($lang->id);

    expect($withStats->translated_strings)->toBe(0)
        ->and($withStats->completion_percentage)->toBe(0.0);
});
