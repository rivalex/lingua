<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Transfer;

use Rivalex\Lingua\Contracts\TranslationRepository;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Support\TranslationIdentityRules;
use Rivalex\Lingua\Support\TranslationLine;
use Rivalex\Lingua\Support\TranslationValuePolicy;
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
        $identityContext = $this->identityContext($existenceIndex);
        $reader = $this->registry->reader($format);

        $headers = null;
        $rowIndex = 0;
        $created = [];

        foreach ($reader->read($filePath) as $rawRow) {
            // Spreadsheet and CSV exports quote the cells that could start a formula: undo it.
            if ($format !== 'json') {
                $rawRow = array_map(TransferSchema::unguardCell(...), $rawRow);
            }

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

            if ($parsed->rejectedKey !== null) {
                $diff->addError($parsed->rejectedKey, 'The key contains a "*" segment or a NUL character.');

                continue;
            }

            $identityError = $this->identityError($parsed, $existenceIndex, $created, $identityContext);
            if ($identityError !== null) {
                $diff->addError($parsed->rawKey, $identityError);

                continue;
            }

            if ($parsed->isVendor && ! $vendorUpdateEnabled) {
                $diff->addSkip($parsed->rawKey, 'vendor row (opt-in disabled)');

                continue;
            }

            $valueError = $this->valueError($parsed, $existenceIndex);
            if ($valueError !== null) {
                $diff->addError($parsed->rawKey, $valueError);

                continue;
            }

            if ($parsed->isVendor) {
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
                $created[$parsed->group ?? ''][] = $parsed->key ?? '';
            }
        }

        return $diff;
    }

    /**
     * Identity error of a resolved row, or null when it is acceptable.
     *
     * Rows that match an existing line are never rejected (their names come from
     * synchronized host files); only identities that would be created are validated, also against
     * the keys created by earlier rows of the same file.
     *
     * @param  array<string, TranslationLine>  $existenceIndex
     * @param  array<string, list<string>>  $created  keys created earlier in the file, by group
     * @param  array{groups: array<array-key, true>, keys: array<array-key, list<string>>}|null  $context  precomputed identityContext(); built here when null
     */
    public function identityError(ParsedRow $parsed, array $existenceIndex, array $created = [], ?array $context = null): ?string
    {
        if (isset($existenceIndex[$this->parsedIndexKey($parsed)])) {
            return null;
        }

        $group = $parsed->group ?? '';

        $error = TranslationIdentityRules::groupError($group)
            ?? ($parsed->isVendor && $parsed->vendorName !== null ? TranslationIdentityRules::vendorError($parsed->vendorName) : null)
            ?? TranslationIdentityRules::keyError($group, $parsed->key ?? '');

        // Vendor rows are never created, so only app rows are compared with the existing identities.
        if ($error !== null || $parsed->isVendor) {
            return $error;
        }

        $key = $parsed->key ?? '';

        if (in_array($key, $created[$group] ?? [], true)) {
            return 'duplicate key in this file';
        }

        $context ??= $this->identityContext($existenceIndex);

        $existingKeys = array_merge($context['keys'][$group] ?? [], $created[$group] ?? []);
        $existingGroups = $context['groups'];
        foreach (array_keys($created) as $createdGroup) {
            $existingGroups[$createdGroup] = true;
        }

        return TranslationIdentityRules::collisionError($group, $key, $existingKeys)
            ?? (isset($existingGroups[$group]) ? null : TranslationIdentityRules::groupCaseError($group, array_map('strval', array_keys($existingGroups))));
    }

    /**
     * The existing groups and the keys of every group (vendor lines excluded from the keys), read once from the index.
     *
     * @param  array<string, TranslationLine>  $existenceIndex
     * @return array{groups: array<array-key, true>, keys: array<array-key, list<string>>}
     */
    public function identityContext(array $existenceIndex): array
    {
        $groups = [];
        $keys = [];

        foreach ($existenceIndex as $line) {
            $groups[$line->group] = true;
            if (! $line->isVendor) {
                $keys[$line->group][] = $line->key;
            }
        }

        return ['groups' => $groups, 'keys' => $keys];
    }

    /**
     * Value error of a resolved row, or null when the value can be written.
     *
     * The type is the one the repository normalizes the value with: the stored type of an existing
     * line, the _type column (default text) of a new one, always text in file mode.
     *
     * @param  array<string, TranslationLine>  $existenceIndex
     */
    public function valueError(ParsedRow $parsed, array $existenceIndex): ?string
    {
        $line = $existenceIndex[$this->parsedIndexKey($parsed)] ?? null;

        if (linguaIsFileMode()) {
            $type = LinguaType::text;
        } elseif ($line !== null) {
            $type = $line->type;
        } else {
            $type = LinguaType::tryFrom($parsed->typeRaw ?? '') ?? LinguaType::text;
            $type = $type === LinguaType::any ? LinguaType::text : $type;
        }

        return TranslationValuePolicy::assertAcceptable($type, $parsed->group ?? 'single', $parsed->key ?? $parsed->rawKey, $parsed->targetValue);
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
