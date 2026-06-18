<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Transfer;

use Rivalex\Lingua\Support\TranslationLine;
use Rivalex\Lingua\Transfer\Enums\TransferFilter;
use Rivalex\Lingua\Transfer\Enums\TransferScope;

/**
 * Pure (no I/O) mapper between TranslationLine DTOs and tabular row arrays.
 *
 * Responsibilities:
 * - lineToRow()      — TranslationLine → one associative row array per scope
 * - parseRow()       — raw CSV/XLSX row → ParsedRow (pre-identity)
 * - resolveIdentity() — fill ParsedRow.group/key/isVendor/vendorName via existence index
 */
final class RowMapper
{
    /**
     * Convert a TranslationLine to an associative row array for export.
     *
     * Returns null when the onlyMissing filter applies and the target locale
     * already has a non-empty value.
     *
     * @param  array<int, string>  $allLocaleCodes  All installed locale codes (multiLocale scope).
     * @return array<string, string>|null
     */
    public function lineToRow(
        TranslationLine $line,
        string $defaultLocale,
        TransferScope $scope,
        ?string $targetLocale,
        TransferFilter $filter,
        string $defaultLocaleCode,
    ): ?array {
        // Apply only-missing filter for bilingual/multiLocale
        if ($filter === TransferFilter::onlyMissing && $scope !== TransferScope::jsonNative) {
            $checkLocale = $scope === TransferScope::bilingual ? $targetLocale : null;
            if ($checkLocale !== null) {
                $targetValue = $line->value($checkLocale);
                if (trim($targetValue) !== '') {
                    return null;
                }
            }
        }

        // Strip vendor:: prefix from groupKey to produce the _key column value
        $rawKey = $line->groupKey;
        if ($line->isVendor && $line->vendor !== null) {
            $prefix = $line->vendor.'::';
            if (str_starts_with($rawKey, $prefix)) {
                $rawKey = substr($rawKey, strlen($prefix));
            }
        }

        $vendorValue = $line->vendor ?? '';

        return match ($scope) {
            TransferScope::bilingual => [
                TransferSchema::KEY => $rawKey,
                TransferSchema::TYPE => $line->type->value,
                TransferSchema::sourceHeader($defaultLocale) => $line->value($defaultLocaleCode),
                TransferSchema::targetHeader($targetLocale ?? $defaultLocale) => $line->value($targetLocale ?? $defaultLocale),
                TransferSchema::VENDOR => $vendorValue,
            ],
            TransferScope::multiLocale => $this->buildMultiLocaleRow($line, $rawKey, $vendorValue, $filter),
            TransferScope::jsonNative => $this->buildJsonNativeRow($line),
        };
    }

    /**
     * Parse a combined headers+values row into a ParsedRow (pre-identity resolution).
     *
     * @param  array<string, string>  $row  Associative row (header => value).
     * @param  list<string>  $headers  Ordered headers array.
     */
    public function parseRow(array $row, array $headers, string $targetLocale): ParsedRow
    {
        $rawKey = $row[TransferSchema::KEY] ?? '';
        $vendor = $row[TransferSchema::VENDOR] ?? '';
        $typeRaw = isset($row[TransferSchema::TYPE]) ? $row[TransferSchema::TYPE] : null;

        // Find target locale column value — header may be "it - Italian" or just the locale code
        $targetValue = $this->findLocaleValue($row, $headers, $targetLocale);

        return new ParsedRow(
            rawKey: $rawKey,
            vendor: $vendor,
            typeRaw: $typeRaw !== '' ? $typeRaw : null,
            targetValue: $targetValue,
        );
    }

    /**
     * Resolve group/key/isVendor/vendorName on a ParsedRow using the existence index.
     *
     * The existence index is keyed by:
     * - "{vendor}::{group}.{key}" for vendor rows
     * - "{group}.{key}" for app rows
     *
     * @param  array<string, TranslationLine>  $existenceIndex
     */
    public function resolveIdentity(ParsedRow $parsed, array $existenceIndex): ParsedRow
    {
        $isVendor = $parsed->vendor !== '';
        $vendorName = $isVendor ? $parsed->vendor : null;

        // Build lookup key matching the existenceIndex format
        $lookupKey = $isVendor
            ? $parsed->vendor.'::'.$parsed->rawKey
            : $parsed->rawKey;

        if (isset($existenceIndex[$lookupKey])) {
            $line = $existenceIndex[$lookupKey];

            return new ParsedRow(
                rawKey: $parsed->rawKey,
                vendor: $parsed->vendor,
                typeRaw: $parsed->typeRaw,
                targetValue: $parsed->targetValue,
                group: $line->group,
                key: $line->key,
                isVendor: $line->isVendor,
                vendorName: $line->vendor,
            );
        }

        // New key: split on first dot only
        $rawKey = $parsed->rawKey;
        if (str_contains($rawKey, '.')) {
            $dotPos = strpos($rawKey, '.');
            $group = substr($rawKey, 0, $dotPos);
            $key = substr($rawKey, $dotPos + 1);
        } else {
            $group = 'single';
            $key = $rawKey;
        }

        return new ParsedRow(
            rawKey: $parsed->rawKey,
            vendor: $parsed->vendor,
            typeRaw: $parsed->typeRaw,
            targetValue: $parsed->targetValue,
            group: $group,
            key: $key,
            isVendor: $isVendor,
            vendorName: $vendorName,
        );
    }

    /**
     * Build a multiLocale row — all locale columns present.
     *
     * @return array<string, string>
     */
    private function buildMultiLocaleRow(
        TranslationLine $line,
        string $rawKey,
        string $vendorValue,
        TransferFilter $filter,
    ): array {
        $row = [
            TransferSchema::KEY => $rawKey,
            TransferSchema::TYPE => $line->type->value,
        ];

        foreach ($line->text as $locale => $value) {
            $row[TransferSchema::targetHeader($locale)] = $value;
        }

        $row[TransferSchema::VENDOR] = $vendorValue;

        return $row;
    }

    /**
     * Build a JSON-native row preserving all fields for lossless round-trip.
     *
     * @return array<string, mixed>
     */
    private function buildJsonNativeRow(TranslationLine $line): array
    {
        return [
            'group' => $line->group,
            'key' => $line->key,
            'groupKey' => $line->groupKey,
            'type' => $line->type->value,
            'text' => $line->text,
            'isVendor' => $line->isVendor,
            'vendor' => $line->vendor,
        ];
    }

    /**
     * Find the value for a given locale in a row, checking both
     * the full header label ("it - Italian") and the bare locale code.
     *
     * @param  array<string, string>  $row
     * @param  list<string>  $headers
     */
    private function findLocaleValue(array $row, array $headers, string $targetLocale): string
    {
        // Direct match by bare locale code
        if (isset($row[$targetLocale])) {
            return $row[$targetLocale];
        }

        // Match header that starts with "{locale} - " or equals the locale
        foreach ($headers as $header) {
            if (TransferSchema::isMeta($header)) {
                continue;
            }
            if (str_starts_with($header, $targetLocale.' - ') || $header === $targetLocale) {
                return $row[$header] ?? '';
            }
        }

        return '';
    }
}
