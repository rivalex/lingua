<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Support;

use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Exceptions\UnsafeTranslationValue;

/**
 * Single policy applied to every translation value written through the repositories.
 *
 * Html values always pass through HtmlSanitizer and are stored sanitized. Text and markdown
 * values are never rewritten: Lingua's own HTML5 tag scanner decides whether they are accepted
 * (only complete, balanced tags and attributes of the sanitizer allowlist, no comments), so the
 * answer does not depend on the libxml build. libxml is a second check: a value HtmlSanitizer
 * would alter is rejected too. Accepted values are stored byte for byte ("a < b", "&laquo; Previous").
 */
final class TranslationValuePolicy
{
    /**
     * Keys (group, key) that a Laravel view prints inside an HTML attribute, so a value may not hold a quote or an angle
     * bracket. Other views print the same keys with "{{ }}", so a value is rejected instead of being encoded.
     * Translator::get() reads a JSON key before "group.item", so the single rows 'pagination.previous' and
     * 'pagination.next' replace the pagination lines of the same views.
     * vendor/laravel/framework/src/Illuminate/Pagination/resources/views/simple-bootstrap-5.blade.php:2
     * prints aria-label="{!! __('Pagination Navigation') !!}"; bootstrap-5.blade.php does the same for
     * pagination.previous and pagination.next.
     */
    private const RAW_ATTRIBUTE_KEYS = [
        ['single', 'Pagination Navigation'],
        ['pagination', 'previous'],
        ['pagination', 'next'],
        ['single', 'pagination.previous'],
        ['single', 'pagination.next'],
    ];

    /** @var list<string> Elements that have no end tag. */
    private const VOID_TAGS = ['br', 'hr', 'img'];

    private const ATTRIBUTE_MESSAGE = '[Lingua] The value of this translation is printed inside an HTML attribute and may not contain ", < or >.';

    /**
     * @throws UnsafeTranslationValue when a text or markdown value holds HTML the sanitizer would remove
     */
    public static function normalize(LinguaType $type, string $value, string $group, string $key): string
    {
        if ($type === LinguaType::html) {
            // The sanitizer turns &quot; into ", so the attribute check runs on its output.
            $sanitized = HtmlSanitizer::sanitize($value);

            if (self::hasAttributeCharacter($group, $key, $sanitized)) {
                throw new UnsafeTranslationValue(self::ATTRIBUTE_MESSAGE);
            }

            return $sanitized;
        }

        if (self::hasAttributeCharacter($group, $key, $value)) {
            throw new UnsafeTranslationValue(self::ATTRIBUTE_MESSAGE);
        }

        if (self::isUnsafe($value)) {
            throw UnsafeTranslationValue::forType($type);
        }

        return $value;
    }

    /**
     * The message normalize() would throw for this value, or null when the value is acceptable.
     * For the validation layers: it applies the same decision as normalize() without throwing.
     */
    public static function assertAcceptable(LinguaType $type, string $group, string $key, string $value): ?string
    {
        if ($type === LinguaType::html) {
            return self::hasAttributeCharacter($group, $key, HtmlSanitizer::sanitize($value)) ? self::ATTRIBUTE_MESSAGE : null;
        }

        if (self::hasAttributeCharacter($group, $key, $value)) {
            return self::ATTRIBUTE_MESSAGE;
        }

        return self::isUnsafe($value) ? UnsafeTranslationValue::messageFor($type) : null;
    }

    private static function hasAttributeCharacter(string $group, string $key, string $value): bool
    {
        return in_array([$group, $key], self::RAW_ATTRIBUTE_KEYS, true) && preg_match('/["<>]/', $value) === 1;
    }

    private static function isUnsafe(string $value): bool
    {
        if (! str_contains($value, '<')) {
            return false;
        }

        $tags = HtmlTagScanner::scan($value);

        if ($tags === null || self::tagsRejected($tags)) {
            return true;
        }

        // libxml stays a second check: a value the sanitizer would change is rejected too.
        return HtmlSanitizer::sanitizeReport($value)[1] > 0;
    }

    /**
     * True when the value is refused by Lingua's own tag scanner alone, without asking libxml.
     *
     * @internal
     */
    public static function scanRejects(string $value): bool
    {
        if (! str_contains($value, '<')) {
            return false;
        }

        $tags = HtmlTagScanner::scan($value);

        return $tags === null || self::tagsRejected($tags);
    }

    /**
     * @param  list<array{type: 'start'|'end', name: string, attributes: list<array{0: string, 1: string}>, selfClosing: bool}>  $tags
     */
    private static function tagsRejected(array $tags): bool
    {
        $stack = [];

        foreach ($tags as $tag) {
            $name = $tag['name'];

            if (! HtmlSanitizer::isAllowedTag($name)) {
                return true;
            }

            if ($tag['type'] === 'end') {
                if ($tag['attributes'] !== [] || array_pop($stack) !== $name) {
                    return true;
                }

                continue;
            }

            $seen = [];

            foreach ($tag['attributes'] as [$attribute, $attributeValue]) {
                if (isset($seen[$attribute]) || ! HtmlSanitizer::isAllowedAttribute($name, $attribute)) {
                    return true;
                }

                $seen[$attribute] = true;

                if (($attribute === 'href' || $attribute === 'src') && ! HtmlSanitizer::isSafeUri($attributeValue)) {
                    return true;
                }
            }

            if (! in_array($name, self::VOID_TAGS, true)) {
                $stack[] = $name;
            }
        }

        return $stack !== [];
    }
}
