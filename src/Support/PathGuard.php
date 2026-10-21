<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Support;

/**
 * Guards path segments against directory traversal attacks.
 */
final class PathGuard
{
    /**
     * Assert that a path segment is safe to use in filesystem operations.
     *
     * Rejects empty strings, directory separators, null bytes, and dot-dot sequences.
     *
     * @throws \InvalidArgumentException on any unsafe segment
     */
    public static function assertSafeSegment(string $segment, string $context = ''): void
    {
        if ($segment === '' ||
            str_contains($segment, '/') ||
            str_contains($segment, '\\') ||
            str_contains($segment, '..') ||
            str_contains($segment, "\0")
        ) {
            throw new \InvalidArgumentException(
                '[Lingua] Unsafe path segment'.($context !== '' ? " ({$context})" : '').": {$segment}"
            );
        }
    }
}
