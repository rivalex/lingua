<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Support;

use Illuminate\Support\Facades\Schema;

/**
 * Request-scoped memo of table existence checks.
 *
 * When the translations table is missing, every translation group loaded during a
 * request would otherwise run its own schema query. Bound as a scoped instance, so it
 * resets between Octane requests and queue jobs.
 */
final class TableProbe
{
    /** @var array<string, bool> */
    private array $missing = [];

    /**
     * Whether the given table does not exist, checked once per request.
     */
    public function missing(string $table): bool
    {
        return $this->missing[$table] ??= ! Schema::hasTable($table);
    }
}
