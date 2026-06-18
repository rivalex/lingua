<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Transfer\Format;

/**
 * Contract for tabular-format writers (CSV, XLSX, ODS).
 *
 * JsonWriter also implements this interface; it accepts pre-encoded row arrays
 * (from ExportService) and ignores the $headers argument.
 */
interface FormatWriter
{
    /**
     * Write $headers and $rows to $path.
     *
     * @param  list<string>  $headers
     * @param  iterable<array<string, mixed>>  $rows
     */
    public function write(string $path, array $headers, iterable $rows): void;

    /**
     * MIME type for HTTP response headers.
     */
    public function mimeType(): string;

    /**
     * File extension (without leading dot).
     */
    public function extension(): string;
}
