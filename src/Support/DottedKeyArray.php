<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Support;

use Illuminate\Support\Arr;

/**
 * Reads and writes dotted keys in nested arrays while respecting the shape the array already has.
 *
 * A lang file may hold a key that itself contains dots, for example
 * ['custom' => ['items.*.name' => ['required' => '...']]]. Plain dot-notation helpers would split it
 * into a different structure. Here the existing shape wins: when some split of the key already ends on a
 * leaf, that leaf is used; otherwise the key is expanded into nested arrays. No wildcard is interpreted.
 */
final class DottedKeyArray
{
    /**
     * Set $value at $key, in the existing shape when there is one, otherwise as a new nested path.
     *
     * @param  array<array-key, mixed>  $array
     */
    public static function set(array &$array, string $key, mixed $value): void
    {
        $path = self::existingPath($array, $key);

        if ($path === null) {
            Arr::set($array, $key, $value);

            return;
        }

        $target = &$array;
        foreach ($path as $segment) {
            $target = &$target[$segment];
        }
        $target = $value;
    }

    /**
     * Whether $key resolves to an entry, in the existing shape or as a nested path.
     *
     * @param  array<array-key, mixed>  $array
     */
    public static function has(array $array, string $key): bool
    {
        return self::existingPath($array, $key) !== null || Arr::has($array, $key);
    }

    /**
     * Remove the leaf that $key resolves to in the existing shape; do nothing when there is none.
     *
     * @param  array<array-key, mixed>  $array
     */
    public static function forget(array &$array, string $key): void
    {
        $path = self::existingPath($array, $key);

        if ($path === null) {
            return;
        }

        $last = array_pop($path);
        $target = &$array;
        foreach ($path as $segment) {
            $target = &$target[$segment];
        }
        unset($target[$last]);
    }

    /**
     * Find the list of array keys whose dot-joined path equals $key and ends on a leaf (not an array).
     *
     * @param  array<array-key, mixed>  $array
     * @return list<array-key>|null
     */
    private static function existingPath(array $array, string $key): ?array
    {
        foreach ($array as $segment => $value) {
            $segmentName = (string) $segment;

            if ($segmentName === $key) {
                if (! is_array($value)) {
                    return [$segment];
                }

                continue;
            }

            if (is_array($value) && str_starts_with($key, $segmentName.'.')) {
                $rest = self::existingPath($value, substr($key, strlen($segmentName) + 1));

                if ($rest !== null) {
                    return [$segment, ...$rest];
                }
            }
        }

        return null;
    }
}
