<?php

namespace Rivalex\Lingua\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use LaravelLang\Locales\Facades\Locales;
use Rivalex\Lingua\Models\Language;

class LanguageFactory extends Factory
{
    protected $model = Language::class;

    public function definition(): array
    {
        $availableLocales = Locales::available()->pluck('code')->toArray();
        $bdLocales = Language::all()->pluck('code')->toArray();
        $safeLocales = array_unique(array_diff($availableLocales, $bdLocales));
        $localeData = Locales::info($safeLocales[array_rand($safeLocales)]);

        return [
            'code' => $localeData->code,
            'regional' => $localeData->regional,
            'type' => $localeData->type,
            'name' => $localeData->localized,
            'native' => $localeData->native,
            'direction' => $localeData->direction,
            'is_default' => false,
            'sort' => fake()->numberBetween(1, 100),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
