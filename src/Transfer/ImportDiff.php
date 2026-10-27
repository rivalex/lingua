<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Transfer;

/**
 * Mutable DTO accumulating the result of an import dry-run.
 *
 * Counters track all rows. The three bucket arrays are capped at 200 entries
 * each to avoid unbounded Livewire state serialisation.
 */
final class ImportDiff
{
    private const MAX_ROWS = 200;

    public int $createCount = 0;

    public int $updateCount = 0;

    public int $skipCount = 0;

    public int $errorCount = 0;

    /** @var list<array{key: string, reason: string}> */
    public array $skipped = [];

    /** @var list<array{key: string, reason: string}> */
    public array $errors = [];

    /** @var list<array{key: string, action: string}> */
    public array $changes = [];

    public bool $vendorUpdateEnabled = false;

    /**
     * Record a skipped row (cap at MAX_ROWS displayed entries).
     */
    public function addSkip(string $key, string $reason): void
    {
        $this->skipCount++;
        if (count($this->skipped) < self::MAX_ROWS) {
            $this->skipped[] = ['key' => $key, 'reason' => $reason];
        }
    }

    /**
     * Record an error row (cap at MAX_ROWS displayed entries).
     */
    public function addError(string $key, string $reason): void
    {
        $this->errorCount++;
        if (count($this->errors) < self::MAX_ROWS) {
            $this->errors[] = ['key' => $key, 'reason' => $reason];
        }
    }

    /**
     * Record a planned create or update (cap at MAX_ROWS displayed entries).
     */
    public function addChange(string $key, string $action): void
    {
        if (count($this->changes) < self::MAX_ROWS) {
            $this->changes[] = ['key' => $key, 'action' => $action];
        }
    }
}
