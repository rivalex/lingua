<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Rivalex\Lingua\Locales\LocaleRegistry;
use Rivalex\Lingua\Models\Language;

class LanguageFactory extends Factory
{
    protected $model = Language::class;

    public function definition(): array
    {
        $registry = app(LocaleRegistry::class);
        $usedCodes = Language::all()->pluck('code')->toArray();
        $available = array_diff($registry->availableCodes(), $usedCodes);
        $code = $available[array_rand($available)];
        $info = $registry->info($code);

        return [
            'code' => $info->code,
            'regional' => $info->regional,
            'type' => $info->type,
            'name' => $info->name,
            'native' => $info->native,
            'direction' => $info->direction,
            'is_default' => false,
            'sort' => fake()->numberBetween(1, 100),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
