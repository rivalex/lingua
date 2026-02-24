<?php

namespace Rivalex\Lingua\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Rivalex\Lingua\Models\Translation;

class TranslationFactory extends Factory
{
    protected $model = Translation::class;

    public function definition(): array
    {
        $key = fake()->word();
        $group = fake()->word();
        $isVendor = fake()->boolean();
        $vendor = $isVendor ? fake()->word() : null;

        return [
            'group' => $key,
            'key' => $group,
            'group_key' => Translation::getGroupKey($key, $group, $isVendor, $vendor),
            'type' => fake()->randomElement(['text', 'html', 'markdown']),
            'text' => [fake()->locale() => fake()->sentence()],
            'is_vendor' => $isVendor,
            'vendor' => $vendor,
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
