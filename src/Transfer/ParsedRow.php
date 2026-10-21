<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Transfer;

/**
 * Immutable DTO produced by RowMapper::parseRow() + resolveIdentity().
 *
 * After parseRow(): rawKey, vendor, typeRaw, targetValue are set.
 * After resolveIdentity(): group, key, isVendor, vendorName are filled in.
 */
final readonly class ParsedRow
{
    /**
     * @param  string  $rawKey  Value from the _key column.
     * @param  string  $vendor  Value from the _vendor column ('' = not vendor).
     * @param  string|null  $typeRaw  Value from the _type column, or null if absent.
     * @param  string  $targetValue  Value of the target locale column.
     * @param  string|null  $group  Resolved group segment (filled by resolveIdentity).
     * @param  string|null  $key  Resolved key segment (filled by resolveIdentity).
     * @param  bool  $isVendor  Whether this is a vendor row (filled by resolveIdentity).
     * @param  string|null  $vendorName  Vendor namespace name (filled by resolveIdentity).
     */
    public function __construct(
        public string $rawKey,
        public string $vendor,
        public ?string $typeRaw,
        public string $targetValue,
        public ?string $group = null,
        public ?string $key = null,
        public bool $isVendor = false,
        public ?string $vendorName = null,
    ) {}
}
