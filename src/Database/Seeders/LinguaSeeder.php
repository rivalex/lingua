<?php

namespace Rivalex\Lingua\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use LaravelLang\Locales\Facades\Locales;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

class LinguaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultLocale = app()->getFallbackLocale() ?? config('lingua.default_locale');

        if (! Locales::installed()->contains($defaultLocale)) {
            Artisan::call('lang:add '.$defaultLocale);
        }

        $defaultLanguage = Locales::info($defaultLocale);

        Language::updateOrCreate(
            ['code' => $defaultLanguage->code, 'regional' => $defaultLanguage->regional],
            [
                'type' => $defaultLanguage->type,
                'name' => $defaultLanguage->locale->name,
                'native' => $defaultLanguage->native,
                'direction' => $defaultLanguage->direction->value,
                'is_default' => true,
            ]
        );

        foreach (Locales::installed() as $locale) {
            if ($locale->code !== $defaultLocale) {
                Language::updateOrCreate(
                    ['code' => $locale->code, 'regional' => $locale->regional],
                    [
                        'type' => $locale->type,
                        'name' => $locale->locale->name,
                        'native' => $locale->native,
                        'direction' => $locale->direction->value,
                        'is_default' => false,
                    ]
                );
            }
        }

        Translation::syncToDatabase();
    }
}
