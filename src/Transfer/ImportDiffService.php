<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Transfer;

use Rivalex\Lingua\Contracts\TranslationRepository;
use Rivalex\Lingua\Support\TranslationLine;
use Rivalex\Lingua\Transfer\Format\FormatRegistry;

/**
 * Parses an uploaded translation file and produces an ImportDiff (dry-run).
 *
 * No writes are performed. The diff describes what would happen on commit:
 * creates, updates, skips (with reasons), and errors (with reasons).
 */
final class ImportDiffService
{
    public function __construct(
        private readonly TranslationRepository $repository,
        private readonly FormatRegistry $registry,
        private readonly RowMapper $mapper,
    ) {}

    /**
     * Parse $filePath in $format and return the dry-run diff for $targetLocale.
     */
    public function diff(
        string $filePath,
        string $format,
        string $targetLocale,
        bool $vendorUpdateEnabled,
    ): ImportDiff {
        $diff = new ImportDiff;
        $diff->vendorUpdateEnabled = $vendorUpdateEnabled;

        $existenceIndex = $this->buildExistenceIndex();
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

            // Pad short rows to header count (some CSV editors trim trailing empty cells)
            $padded = $rawRow + array_fill(0, count($headers), '');
            $assoc = array_combine($headers, array_slice($padded, 0, count($headers)));

            $parsed = $this->mapper->parseRow($assoc, $headers, $targetLocale);

            if (trim($parsed->rawKey) === '') {
                $diff->addSkip('', 'empty key');

                continue;
            }

            if (trim($parsed->targetValue) === '') {
                $diff->addSkip($parsed->rawKey, 'empty target value');

                continue;
            }

            $parsed = $this->mapper->resolveIdentity($parsed, $existenceIndex);

            if ($parsed->isVendor) {
                if (! $vendorUpdateEnabled) {
                    $diff->addSkip($parsed->rawKey, 'vendor row (opt-in disabled)');

                    continue;
                }

                $indexKey = $this->parsedIndexKey($parsed);
                if (! isset($existenceIndex[$indexKey])) {
                    $diff->addSkip($parsed->rawKey, 'vendor key not found (non-existent vendor rows cannot be created)');

                    continue;
                }

                $diff->updateCount++;
                $diff->addChange($parsed->rawKey, 'update (vendor)');

                continue;
            }

            // App row
            $indexKey = $this->parsedIndexKey($parsed);
            if (isset($existenceIndex[$indexKey])) {
                $diff->updateCount++;
                $diff->addChange($parsed->rawKey, 'update');
            } else {
                $diff->createCount++;
                $diff->addChange($parsed->rawKey, 'create');
            }
        }

        return $diff;
    }

    /**
     * Build an in-memory existence index from all current repository lines.
     *
     * @return array<string, TranslationLine>
     */
    public function buildExistenceIndex(): array
    {
        $index = [];
        foreach ($this->repository->all(includeVendor: true) as $line) {
            $index[$this->lineIndexKey($line)] = $line;
        }

        return $index;
    }

    /**
     * Canonical index key for a TranslationLine.
     */
    public function lineIndexKey(TranslationLine $line): string
    {
        if ($line->isVendor && $line->vendor !== null) {
            return $line->vendor.'::'.$line->group.'.'.$line->key;
        }

        return $line->group.'.'.$line->key;
    }

    /**
     * Canonical index key for a ParsedRow (after resolveIdentity).
     */
    public function parsedIndexKey(ParsedRow $parsed): string
    {
        if ($parsed->isVendor && $parsed->vendorName !== null) {
            return $parsed->vendorName.'::'.$parsed->group.'.'.$parsed->key;
        }

        return ($parsed->group ?? '').'.'.$parsed->key;
    }
}
