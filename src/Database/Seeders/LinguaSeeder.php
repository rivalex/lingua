<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Database\Seeders;

use Illuminate\Database\Seeder;
use Rivalex\Lingua\Locales\LocaleRegistry;
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
        $registry = app(LocaleRegistry::class);
        $info = $registry->info($defaultLocale);

        Language::updateOrCreate(
            ['code' => $info?->code ?? $defaultLocale, 'regional' => $info?->regional ?? null],
            [
                'type' => $info?->type ?? 'Latn',
                'name' => $info?->name ?? $defaultLocale,
                'native' => $info?->native ?? $defaultLocale,
                'direction' => $info?->direction ?? 'ltr',
                'is_default' => true,
            ]
        );

        Translation::syncToDatabase();
    }
}
