<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Whitelist-based HTML sanitizer for translation previews.
 *
 * Replaces the previous strip_tags() approach, which removed disallowed
 * tags but preserved ALL attributes on allowed ones - leaving event
 * handlers (onerror, onclick, ...) and javascript:/data: URIs intact
 * and therefore exploitable as stored XSS in the admin UI.
 *
 * Strategy: parse with DOMDocument, unwrap any element not in the tag
 * whitelist (keeping its children), drop every attribute that is not
 * explicitly allowed for that tag, and validate URI attributes against
 * a scheme whitelist. Output is the re-serialized inner HTML.
 */
final class HtmlSanitizer
{
    /** @var list<string> Tags allowed in the sanitized output. */
    private const ALLOWED_TAGS = [
        'p', 'br', 'b', 'i', 'em', 'strong', 'u', 's',
        'ul', 'ol', 'li', 'a', 'img',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'span', 'div', 'table', 'tr', 'td', 'th', 'thead', 'tbody',
        'hr', 'blockquote', 'pre', 'code', 'sub', 'sup',
    ];

    /** @var array<string, list<string>> Attributes allowed per tag. */
    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'title'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
    ];

    /** @var list<string> Attribute names whose value is a URI and must pass the scheme check. */
    private const URI_ATTRIBUTES = ['href', 'src'];

    /** @var list<string> URI schemes allowed in href/src values. */
    private const ALLOWED_URI_SCHEMES = ['http', 'https', 'mailto'];

    /**
     * Sanitize an HTML fragment for safe rendering with unescaped Blade output.
     *
     * Returns an empty string for blank input. Never throws: a fragment
     * that cannot be parsed yields whatever DOMDocument could recover.
     *
     * @param  string  $html  Untrusted HTML fragment (translation value).
     * @return string Sanitized HTML containing only whitelisted tags/attributes.
     */
    public static function sanitize(string $html): string
    {
        return self::sanitizeReport($html)[0];
    }

    /**
     * Sanitize an HTML fragment and count what was taken out of it.
     *
     * The count covers every element removed or unwrapped, every attribute removed and every
     * comment or CDATA section dropped, plus the html, head and body tags the parser discards
     * on its own. A count of 0 means the sanitizer changed no markup.
     *
     * @param  string  $html  Untrusted HTML fragment (translation value).
     * @return array{0: string, 1: int} The sanitized HTML and the number of removals.
     */
    public static function sanitizeReport(string $html): array
    {
        if (trim($html) === '') {
            return ['', 0];
        }

        $dom = new DOMDocument;

        $previous = libxml_use_internal_errors(true);

        // The XML prolog forces UTF-8 interpretation; the wrapper div gives
        // a single stable root to read the sanitized fragment back from.
        $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="lingua-sanitizer-root">'.$html.'</div>',
            LIBXML_NONET
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $dom->getElementById('lingua-sanitizer-root');

        if ($root === null) {
            return ['', 1];
        }

        // The parser drops these document tags (and merges their attributes) without a trace in the DOM.
        $removed = preg_match_all('/<\s*\/?\s*(?:html|head|body)\b/i', $html);
        $removed = $removed === false ? 0 : $removed;

        self::sanitizeChildren($root, $removed);

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $dom->saveHTML($child);
        }

        return [trim($output), $removed];
    }

    /**
     * Recursively sanitize all child nodes of the given node.
     *
     * Children are snapshotted before iteration because unwrapping or
     * removing nodes mutates the live DOM node list.
     */
    private static function sanitizeChildren(DOMNode $node, int &$removed): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if (! $child instanceof DOMElement) {
                // Text, comments, CDATA: keep text, drop comments/CDATA.
                if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_CDATA_SECTION_NODE) {
                    $node->removeChild($child);
                    $removed++;
                }

                continue;
            }

            $tag = strtolower($child->tagName);

            // script/style content must never leak as text - remove entirely.
            if (in_array($tag, ['script', 'style'], true)) {
                $node->removeChild($child);
                $removed++;

                continue;
            }

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                self::unwrap($child, $removed);

                continue;
            }

            self::filterAttributes($child, $tag, $removed);
            self::sanitizeChildren($child, $removed);
        }
    }

    /**
     * Replace an element with its own (sanitized) children.
     */
    private static function unwrap(DOMElement $element, int &$removed): void
    {
        $parent = $element->parentNode;

        if ($parent === null) {
            return;
        }

        $removed++;

        self::sanitizeChildren($element, $removed);

        foreach (iterator_to_array($element->childNodes) as $child) {
            $parent->insertBefore($child, $element);
        }

        $parent->removeChild($element);
    }

    /**
     * Remove every attribute not whitelisted for the tag and validate URI values.
     */
    private static function filterAttributes(DOMElement $element, string $tag, int &$removed): void
    {
        $allowed = self::ALLOWED_ATTRIBUTES[$tag] ?? [];

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->name);

            if (! in_array($name, $allowed, true)) {
                $element->removeAttribute($attribute->name);
                $removed++;

                continue;
            }

            if (in_array($name, self::URI_ATTRIBUTES, true) && ! self::isSafeUri($attribute->value)) {
                $element->removeAttribute($attribute->name);
                $removed++;
            }
        }
    }

    /**
     * Whether a tag name (lowercase) is in the allowlist.
     */
    public static function isAllowedTag(string $tag): bool
    {
        return in_array($tag, self::ALLOWED_TAGS, true);
    }

    /**
     * Whether an attribute name (lowercase) is allowed for a tag (lowercase).
     */
    public static function isAllowedAttribute(string $tag, string $attribute): bool
    {
        return in_array($attribute, self::ALLOWED_ATTRIBUTES[$tag] ?? [], true);
    }

    /**
     * Accept relative URIs and absolute URIs with a whitelisted scheme.
     */
    public static function isSafeUri(string $uri): bool
    {
        $uri = trim($uri);

        // Decode entities and strip control characters that browsers ignore
        // when resolving schemes (defence against "java\tscript:" obfuscation).
        $normalized = strtolower((string) preg_replace('/[\x00-\x20]+/', '', html_entity_decode($uri, ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        // Browsers decode numeric references without the closing ";", PHP does not: what is left is not a safe URI.
        if (str_contains($normalized, '&#')) {
            return false;
        }

        // Protocol-relative ("//host"), backslash-led ("\\host") and mixed ("/\host")
        // URIs are resolved by browsers as external hosts (a backslash counts as a
        // slash): they are not relative paths.
        if (str_starts_with($normalized, '\\') || preg_match('~^/[/\\\\]~', $normalized) === 1) {
            return false;
        }

        if ($normalized === '' || ! str_contains($normalized, ':')) {
            return true; // Relative URI - safe.
        }

        // A ':' before any '/', '?' or '#' means an explicit scheme.
        $colon = strpos($normalized, ':');
        $delimiter = strcspn($normalized, '/?#');

        if ($colon > $delimiter) {
            return true; // ':' belongs to path/query - still relative.
        }

        return in_array(substr($normalized, 0, $colon), self::ALLOWED_URI_SCHEMES, true);
    }
}
