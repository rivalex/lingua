<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Transfer\Format;

/**
 * Contract for tabular-format readers (CSV, XLSX, ODS, JSON).
 *
 * The first yielded row is always the headers row.
 */
interface FormatReader
{
    /**
     * Yield rows from $path, one indexed array per row.
     * The first yielded row contains the column headers.
     *
     * @return iterable<int, list<string>>
     */
    public function read(string $path): iterable;
}
