<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Transfer\Format;

/**
 * Writes translation rows to a CSV file.
 *
 * Formula-injection guard: any cell value that starts with = + - @ TAB or CR
 * is prefixed with a single quote so spreadsheet applications treat it as text.
 */
final class CsvWriter implements FormatWriter
{
    /**
     * Write headers and rows to $path as UTF-8 CSV.
     *
     * @param  list<string>  $headers
     * @param  iterable<array<string, mixed>>  $rows
     */
    public function write(string $path, array $headers, iterable $rows): void
    {
        $handle = fopen($path, 'w');

        if ($handle === false) {
            throw new \RuntimeException("Cannot open file for writing: {$path}");
        }

        try {
            fputcsv($handle, array_map($this->guard(...), $headers), ',', '"', '\\');

            foreach ($rows as $row) {
                fputcsv($handle, array_map($this->guard(...), array_values($row)), ',', '"', '\\');
            }
        } finally {
            fclose($handle);
        }
    }

    public function mimeType(): string
    {
        return 'text/csv';
    }

    public function extension(): string
    {
        return 'csv';
    }

    /**
     * Prefix dangerous formula-start characters with a single quote.
     */
    private function guard(mixed $value): string
    {
        $cell = (string) $value;

        if ($cell !== '' && preg_match('/^[=+\-@\t\r]/', $cell)) {
            return "'".$cell;
        }

        return $cell;
    }
}
