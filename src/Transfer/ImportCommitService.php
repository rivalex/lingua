<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Transfer;

use Illuminate\Support\Facades\DB;
use Rivalex\Lingua\Contracts\TranslationRepository;
use Rivalex\Lingua\Database\DatabaseRepository;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Support\TranslationLine;
use Rivalex\Lingua\Transfer\Format\FormatRegistry;

/**
 * Commits an import by re-parsing the file and applying changes to the repository.
 *
 * Re-derives the diff on every commit (never trusts cached state) so the
 * committed write exactly matches what the file contains.
 *
 * Rules enforced:
 * - Vendor rows: setValue() on existing only; never create/delete with isVendor=true.
 * - _source and meta columns: silently ignored.
 * - Type precedence: see plan §8.
 */
final class ImportCommitService
{
    public function __construct(
        private readonly TranslationRepository $repository,
        private readonly FormatRegistry $registry,
        private readonly RowMapper $mapper,
        private readonly ImportDiffService $diffService,
    ) {}

    /**
     * Re-parse $filePath and commit all changes for $targetLocale.
     *
     * Returns the ImportDiff (re-derived, not cached) for display.
     */
    public function commit(
        string $filePath,
        string $format,
        string $targetLocale,
        bool $vendorUpdateEnabled,
    ): ImportDiff {
        // Derive the diff BEFORE writing so returned counts match what was applied.
        $diff = $this->diffService->diff($filePath, $format, $targetLocale, $vendorUpdateEnabled);

        $existenceIndex = $this->diffService->buildExistenceIndex();

        $this->withTransaction(function () use ($filePath, $format, $targetLocale, $vendorUpdateEnabled, $existenceIndex): void {
            $this->applyRows($filePath, $format, $targetLocale, $vendorUpdateEnabled, $existenceIndex);
        });

        return $diff;
    }

    /**
     * Parse and apply all rows in the file to the repository.
     *
     * @param  array<string, TranslationLine>  $existenceIndex
     */
    private function applyRows(
        string $filePath,
        string $format,
        string $targetLocale,
        bool $vendorUpdateEnabled,
        array $existenceIndex,
    ): void {
        $reader = $this->registry->reader($format);
        $headers = null;
        $rowIndex = 0;

        foreach ($reader->read($filePath) as $rawRow) {
            if ($rowIndex === 0) {
                $headers = $rawRow;
                $rowIndex++;

                continue;
            }

            $rowIndex++;

            if ($headers === null || count($rawRow) === 0) {
                continue;
            }

            $padded = $rawRow + array_fill(0, count($headers), '');
            $assoc = array_combine($headers, array_slice($padded, 0, count($headers)));

            $parsed = $this->mapper->parseRow($assoc, $headers, $targetLocale);

            if (trim($parsed->rawKey) === '' || trim($parsed->targetValue) === '') {
                continue;
            }

            $parsed = $this->mapper->resolveIdentity($parsed, $existenceIndex);

            if ($parsed->isVendor) {
                if (! $vendorUpdateEnabled) {
                    continue;
                }

                $indexKey = $this->diffService->parsedIndexKey($parsed);
                $line = $existenceIndex[$indexKey] ?? null;
                if ($line === null) {
                    continue; // non-existent vendor row — skip, guard enforced
                }

                $this->repository->setValue($line, $targetLocale, $parsed->targetValue);

                continue;
            }

            // App row
            $indexKey = $this->diffService->parsedIndexKey($parsed);
            $line = $existenceIndex[$indexKey] ?? null;

            if ($line !== null) {
                // Update existing: apply type override per plan §8
                $this->repository->setValue($line, $targetLocale, $parsed->targetValue);
                $this->applyTypeOverride($line, $parsed);
            } else {
                // Create new key
                $type = LinguaType::tryFrom($parsed->typeRaw ?? '') ?? LinguaType::text;

                // Exclude 'any' as a storable type (it's a filter value only)
                if ($type === LinguaType::any) {
                    $type = LinguaType::text;
                }

                $this->repository->create(
                    group: $parsed->group ?? 'single',
                    key: $parsed->key ?? $parsed->rawKey,
                    type: $type,
                    locale: $targetLocale,
                    value: $parsed->targetValue,
                    isVendor: false,
                    vendor: null,
                );
            }
        }
    }

    /**
     * Apply type override for an existing key per plan §8:
     * - If _type column absent or invalid: keep stored type (no-op).
     * - If _type column has a valid LinguaType value: update to that type.
     *
     * File-mode: updateMeta() is a no-op for same group/key — not an error.
     */
    private function applyTypeOverride(TranslationLine $line, ParsedRow $parsed): void
    {
        if ($parsed->typeRaw === null || $parsed->typeRaw === '') {
            return;
        }

        $newType = LinguaType::tryFrom($parsed->typeRaw);

        // Invalid or 'any' — keep stored type
        if ($newType === null || $newType === LinguaType::any) {
            return;
        }

        // Same type — no-op
        if ($newType === $line->type) {
            return;
        }

        try {
            $this->repository->updateMeta($line, $line->group, $line->key, $newType);
        } catch (\RuntimeException) {
            // File-mode: updateMeta throws if group/key would change.
            // Since we pass the same group/key, this should not throw,
            // but guard defensively — type override is best-effort in file-mode.
        }
    }

    /**
     * Wrap the callback in a DB transaction when using DatabaseRepository;
     * otherwise execute directly (file-mode uses sequential atomic writes).
     */
    private function withTransaction(callable $fn): void
    {
        if ($this->repository instanceof DatabaseRepository) {
            DB::transaction($fn);
        } else {
            $fn();
        }
    }
}
