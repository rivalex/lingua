<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Support;

use Closure;

/**
 * Request-scoped memo of expensive reads.
 *
 * A value is resolved once per request and stays until it is forgotten, so a page that
 * asks for the same aggregate several times scans the table once. Bound as a scoped
 * instance, so it resets between Octane requests and queue jobs.
 */
final class RequestMemo
{
    /** @var array<string, mixed> */
    private array $values = [];

    /**
     * Return the memoized value for $key, resolving it on first use.
     *
     * @template T
     *
     * @param  Closure(): T  $resolve
     * @return T
     */
    public function remember(string $key, Closure $resolve): mixed
    {
        if (! array_key_exists($key, $this->values)) {
            $this->values[$key] = $resolve();
        }

        return $this->values[$key];
    }

    /**
     * Drop the memoized value for $key.
     */
    public function forget(string $key): void
    {
        unset($this->values[$key]);
    }
}
