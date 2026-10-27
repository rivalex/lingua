<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Support;

/**
 * Minimal HTML5 tag scanner used by TranslationValuePolicy.
 *
 * It does not depend on libxml: it walks the tag states of the HTML5 tokenizer and returns the start and
 * end tags of a value with their attributes. Quoted attribute values are opaque, so a ">" inside "..." or
 * '...' does not close the tag. Only the "before attribute value" state opens a quoted value: a quote anywhere
 * else in a tag is an HTML parse error that browsers recover from differently from a naive scanner, so the
 * whole value is refused.
 */
final class HtmlTagScanner
{
    /**
     * Returns the tags in document order, or null when the value is not acceptable markup (comment, processing
     * instruction, bogus comment, trailing "<", unclosed tag or stray quote).
     *
     * @return list<array{type: 'start'|'end', name: string, attributes: list<array{0: string, 1: string}>, selfClosing: bool}>|null
     */
    public static function scan(string $value): ?array
    {
        $length = strlen($value);
        $tags = [];
        $i = 0;

        while (($i = strpos($value, '<', $i)) !== false) {
            $next = $value[$i + 1] ?? '';

            if ($next === '' || $next === '!' || $next === '?') {
                return null;
            }

            $end = false;
            $nameStart = $i + 1;

            if ($next === '/') {
                if (! self::isLetter($value[$i + 2] ?? '')) {
                    return null;
                }

                $end = true;
                $nameStart = $i + 2;
            } elseif (! self::isLetter($next)) {
                $i++;

                continue;
            }

            $j = $nameStart;

            while ($j < $length && ! self::isSpace($value[$j]) && $value[$j] !== '/' && $value[$j] !== '>') {
                if ($value[$j] === '"' || $value[$j] === "'") {
                    return null;
                }

                $j++;
            }

            if ($j >= $length) {
                return null;
            }

            $tag = self::scanAttributes($value, $j, $length);

            if ($tag === null) {
                return null;
            }

            [$attributes, $selfClosing, $closeAt] = $tag;

            $tags[] = [
                'type' => $end ? 'end' : 'start',
                'name' => strtolower(substr($value, $nameStart, $j - $nameStart)),
                'attributes' => $attributes,
                'selfClosing' => $selfClosing,
            ];

            $i = $closeAt + 1;
        }

        return $tags;
    }

    /**
     * Walks the attribute states from just after the tag name.
     *
     * @return array{0: list<array{0: string, 1: string}>, 1: bool, 2: int}|null attributes, self-closing flag and the offset of the closing ">"; null when the tag is not closed or holds a stray quote
     */
    private static function scanAttributes(string $value, int $from, int $length): ?array
    {
        $state = 'beforeName';
        $attributes = [];
        $name = '';
        $attrValue = '';
        $selfClosing = false;

        $flush = static function () use (&$attributes, &$name, &$attrValue): void {
            if ($name !== '') {
                $attributes[] = [strtolower($name), html_entity_decode($attrValue, ENT_QUOTES | ENT_HTML5, 'UTF-8')];
            }

            $name = '';
            $attrValue = '';
        };

        for ($j = $from; $j < $length; $j++) {
            $char = $value[$j];
            $space = self::isSpace($char);
            $quote = $char === '"' || $char === "'";

            switch ($state) {
                case 'dq':
                case 'sq':
                    if ($char === ($state === 'dq' ? '"' : "'")) {
                        $flush();
                        $state = 'afterQuoted';
                    } else {
                        $attrValue .= $char;
                    }

                    break;

                case 'beforeValue':
                    if ($space) {
                        break;
                    }

                    if ($char === '>') {
                        $flush();

                        return [$attributes, $selfClosing, $j];
                    }

                    if ($char === '"') {
                        $state = 'dq';
                    } elseif ($char === "'") {
                        $state = 'sq';
                    } else {
                        $state = 'unquoted';
                        $attrValue .= $char;
                    }

                    break;

                case 'unquoted':
                    if ($space) {
                        $flush();
                        $state = 'beforeName';
                    } elseif ($char === '>') {
                        $flush();

                        return [$attributes, $selfClosing, $j];
                    } elseif ($quote) {
                        return null;
                    } else {
                        $attrValue .= $char;
                    }

                    break;

                default:
                    if ($quote) {
                        return null;
                    }

                    if ($char === '>') {
                        $flush();

                        return [$attributes, $selfClosing, $j];
                    }

                    if ($space) {
                        if ($state === 'attrName' || $state === 'afterName') {
                            $state = 'afterName';
                        } else {
                            $state = 'beforeName';
                        }
                    } elseif ($char === '/') {
                        $flush();
                        $selfClosing = ($value[$j + 1] ?? '') === '>';
                        $state = 'beforeName';
                    } elseif ($char === '=' && ($state === 'attrName' || $state === 'afterName')) {
                        $state = 'beforeValue';
                    } elseif ($state === 'attrName') {
                        $name .= $char;
                    } else {
                        // beforeName, afterName and afterQuoted: a new attribute starts here.
                        $flush();
                        $name = $char;
                        $state = 'attrName';
                    }
            }
        }

        return null;
    }

    private static function isLetter(string $char): bool
    {
        return $char !== '' && preg_match('/^[a-zA-Z]$/', $char) === 1;
    }

    private static function isSpace(string $char): bool
    {
        return $char === ' ' || $char === "\t" || $char === "\n" || $char === "\r" || $char === "\f";
    }
}
