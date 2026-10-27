<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Rivalex\Lingua\Models\Translation;

/**
 * Factory for the Translation model.
 *
 * group_key is intentionally NOT set here: the model's creating/saving
 * hooks compute it from group/key/is_vendor/vendor. The previous version
 * called the non-existent Translation::getGroupKey() and swapped the
 * group/key variables, making the factory unusable.
 */
class TranslationFactory extends Factory
{
    protected $model = Translation::class;

    public function definition(): array
    {
        $isVendor = fake()->boolean();

        return [
            'group' => fake()->word(),
            'key' => fake()->unique()->word(),
            'type' => fake()->randomElement(['text', 'html', 'markdown']),
            'text' => ['en' => fake()->sentence()],
            'is_vendor' => $isVendor,
            'vendor' => $isVendor ? fake()->word() : null,
        ];
    }

    /**
     * State: non-vendor translation line.
     */
    public function core(): static
    {
        return $this->state(fn (): array => ['is_vendor' => false, 'vendor' => null]);
    }

    /**
     * State: vendor translation line for the given package name.
     */
    public function vendor(string $vendor): static
    {
        return $this->state(fn (): array => ['is_vendor' => true, 'vendor' => $vendor]);
    }
}
