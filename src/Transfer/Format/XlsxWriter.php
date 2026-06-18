<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Transfer\Format;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

/**
 * XLSX writer using OpenSpout (openspout/openspout).
 *
 * Only instantiated when SpreadsheetSupport::available() returns true.
 */
final class XlsxWriter implements FormatWriter
{
    /**
     * @param  list<string>  $headers
     * @param  iterable<array<string, mixed>>  $rows
     */
    public function write(string $path, array $headers, iterable $rows): void
    {
        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues($this->guardRow($headers)));

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($this->guardRow(array_values($row))));
        }

        $writer->close();
    }

    public function mimeType(): string
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }

    public function extension(): string
    {
        return 'xlsx';
    }

    /**
     * Apply formula-injection guard (same rule as CsvWriter).
     *
     * @param  list<mixed>  $cells
     * @return list<string>
     */
    private function guardRow(array $cells): array
    {
        return array_map(function (mixed $value): string {
            $cell = (string) $value;

            return ($cell !== '' && preg_match('/^[=+\-@\t\r]/', $cell)) ? "'".$cell : $cell;
        }, $cells);
    }
}
