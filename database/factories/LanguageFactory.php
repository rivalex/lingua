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
        $locale = fake()->locale();
        $localeData = Locales::info($locale);
        return [
            'code' => $localeData->code,
            'regional' => $localeData->regional,
            'type' => $localeData->type,
            'name' => $localeData->locale->name,
            'native' => $localeData->native,
            'direction' => $localeData->direction->value,
            'is_default' => false,
            'sort' => fake()->numberBetween(1, 100),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
