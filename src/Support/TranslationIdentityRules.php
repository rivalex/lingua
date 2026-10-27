<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Support;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Rivalex\Lingua\Contracts\TranslationRepository;

/**
 * Single source of truth for the shape of a translation identity (group, vendor, key, locale).
 *
 * Applied to writes coming from the UI, import and facade. Synchronization from the
 * host's own lang/ files is not validated, so existing installs keep working.
 */
final class TranslationIdentityRules
{
    private const NAME_PATTERN = '/^[A-Za-z0-9][A-Za-z0-9_-]{0,63}\z/';

    private const LOCALE_PATTERN = '/^[a-zA-Z]{2,8}([_-][a-zA-Z0-9]{1,8})*\z/';

    private const MAX_KEY_LENGTH = 300;

    public static function groupError(string $group): ?string
    {
        if ($group === 'single' || preg_match(self::NAME_PATTERN, $group) === 1) {
            return null;
        }

        return 'The group must be "single" or start with a letter or digit and contain only letters, digits, hyphens and underscores (64 characters at most).';
    }

    public static function vendorError(string $vendor): ?string
    {
        if (preg_match(self::NAME_PATTERN, $vendor) === 1) {
            return null;
        }

        return 'The vendor must start with a letter or digit and contain only letters, digits, hyphens and underscores (64 characters at most).';
    }

    public static function keyError(string $group, string $key): ?string
    {
        if (trim($key) === '') {
            return 'The key must not be empty.';
        }

        if (mb_strlen($key) > self::MAX_KEY_LENGTH) {
            return 'The key must not be longer than '.self::MAX_KEY_LENGTH.' characters.';
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $key) === 1) {
            return 'The key must not contain control characters.';
        }

        // The unique index on (group, key) squishes the key: such a key would collide with its squished form.
        if ($key !== Str::squish($key)) {
            return 'The key must not have leading, trailing or repeated whitespace (spaces, tabs or non-breaking spaces).';
        }

        // The pipe separates the parts of a translation identity ("group|key|vendor flag|vendor").
        if (str_contains($key, '|')) {
            return 'The key must not contain the "|" character.';
        }

        // data_set() and data_forget() treat a dot segment exactly "*" as a wildcard, in every group.
        if (preg_match('/(^|\.)\*(\.|$)/', $key) === 1) {
            return 'The key must not contain a "*" segment.';
        }

        if ($group !== 'single') {
            if (str_contains($key, '*')) {
                return 'The key must not contain the "*" character.';
            }

            if (preg_match('/^\.|\.\.|\.$/', $key) === 1) {
                return 'The key must not contain empty dot segments.';
            }
        }

        return null;
    }

    /**
     * The keys of one group (within one vendor namespace when $isVendor), read through the repository.
     *
     * @return list<string>
     */
    public static function existingKeys(TranslationRepository $repo, string $group, bool $isVendor, ?string $vendor): array
    {
        $keys = [];

        foreach ($repo->byGroup($group) as $line) {
            if ($line->isVendor === $isVendor && $line->vendor === $vendor) {
                $keys[] = (string) $line->key;
            }
        }

        return $keys;
    }

    /**
     * Error when $key would collide with a key of the same group and vendor.
     *
     * Outside "single" a key is a dotted path: "failed" and "failed.x" cannot coexist because
     * one is a string and the other an array at the same node. "single" keys are written
     * literally, so only the case check applies to them. A key equal to an existing one is not a
     * collision, but a key that differs from an existing one only by case is, in every group.
     *
     * @param  iterable<int, string>  $existingKeys
     */
    public static function collisionError(string $group, string $key, iterable $existingKeys): ?string
    {
        foreach ($existingKeys as $existing) {
            $existing = (string) $existing;

            if ($existing === $key) {
                continue;
            }

            if (mb_strtolower($existing) === mb_strtolower($key)) {
                return 'The key differs only by case from the existing key "'.$existing.'".';
            }

            if ($group === 'single') {
                continue;
            }

            if (str_starts_with($key, $existing.'.')) {
                return 'The key conflicts with the existing key "'.$existing.'", which is a prefix of it.';
            }

            if (str_starts_with($existing, $key.'.')) {
                return 'The key conflicts with the existing key "'.$existing.'", which starts with it.';
            }
        }

        return null;
    }

    /**
     * Error when an existing group differs from $group only by case (file names clash on case-insensitive filesystems).
     *
     * @param  iterable<int, string>  $existingGroups
     */
    public static function groupCaseError(string $group, iterable $existingGroups): ?string
    {
        foreach ($existingGroups as $existing) {
            $existing = (string) $existing;

            if ($existing !== $group && mb_strtolower($existing) === mb_strtolower($group)) {
                return 'The group conflicts with the existing group "'.$existing.'", which differs only by case.';
            }
        }

        return null;
    }

    public static function localeError(string $locale): ?string
    {
        if (preg_match(self::LOCALE_PATTERN, $locale) === 1) {
            return null;
        }

        return 'The locale format is invalid.';
    }

    /**
     * @throws InvalidArgumentException on the first invalid part
     */
    public static function assertValid(string $group, string $key, ?string $vendor, ?string $locale): void
    {
        $error = self::groupError($group)
            ?? ($vendor !== null ? self::vendorError($vendor) : null)
            ?? self::keyError($group, $key)
            ?? ($locale !== null ? self::localeError($locale) : null);

        if ($error !== null) {
            throw new InvalidArgumentException('[Lingua] '.$error);
        }
    }

    /**
     * @throws InvalidArgumentException when the locale format is invalid
     */
    public static function assertLocale(string $locale): void
    {
        $error = self::localeError($locale);

        if ($error !== null) {
            throw new InvalidArgumentException('[Lingua] '.$error);
        }
    }
}
