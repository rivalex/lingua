<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Transfer\Format;

use OpenSpout\Reader\XLSX\Reader;

/**
 * XLSX reader using OpenSpout (openspout/openspout).
 *
 * Only instantiated when SpreadsheetSupport::available() returns true.
 * Reads only the first sheet.
 */
final class XlsxReader implements FormatReader
{
    /**
     * @return iterable<int, list<string>>
     */
    public function read(string $path): iterable
    {
        $reader = new Reader;
        $reader->open($path);

        $index = 0;
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                yield $index++ => array_map('strval', $row->toArray());
            }
            break; // only first sheet
        }

        $reader->close();
    }
}
