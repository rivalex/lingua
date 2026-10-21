<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Support;

/**
 * Request-scoped circuit breaker for the translation cache store.
 *
 * Once a cache read or write fails, the circuit opens and every later translation
 * lookup in the same request skips the store and goes straight to the database.
 * Without it, each translation group loaded during a request would retry the
 * connection and wait for the store's timeout again, multiplying the outage latency.
 *
 * Bound as a scoped instance, so it resets between Octane requests and queue jobs.
 */
final class CacheCircuit
{
    private bool $open = false;

    /**
     * Whether the cache store has failed during the current request.
     */
    public function isOpen(): bool
    {
        return $this->open;
    }

    /**
     * Mark the cache store as unavailable for the rest of the request.
     */
    public function open(): void
    {
        $this->open = true;
    }
}
