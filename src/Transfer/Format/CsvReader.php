<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Transfer\Format;

/**
 * Reads a CSV file row by row.
 *
 * The first yielded row contains the header values.
 * Yields each row as an indexed (non-associative) array of strings.
 */
final class CsvReader implements FormatReader
{
    /**
     * @return iterable<int, list<string>>
     */
    public function read(string $path): iterable
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new \RuntimeException("Cannot open file for reading: {$path}");
        }

        try {
            $index = 0;
            while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                yield $index++ => array_map('strval', $row);
            }
        } finally {
            fclose($handle);
        }
    }
}
