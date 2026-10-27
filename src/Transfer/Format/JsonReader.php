<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Transfer\Format;

/**
 * Reads a native JSON export file.
 *
 * The first yielded "row" is a synthetic headers array derived from the keys
 * of the first entry. Subsequent rows are the entry arrays (indexed).
 * This keeps the FormatReader interface consistent with CSV/XLSX readers.
 */
final class JsonReader implements FormatReader
{
    /**
     * @return iterable<int, list<string>>
     */
    public function read(string $path): iterable
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new \RuntimeException("Cannot read JSON file: {$path}");
        }

        /** @var list<array<string, mixed>> $data */
        $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($data) || empty($data)) {
            return;
        }

        // First row: headers (keys of first entry)
        $headers = array_keys($data[0]);
        yield 0 => $headers;

        // Subsequent rows: values in same key order
        foreach ($data as $i => $entry) {
            $row = [];
            foreach ($headers as $header) {
                $value = $entry[$header] ?? '';
                // Encode nested arrays (e.g. 'text' map) as JSON strings for uniform string handling
                $row[] = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) : (string) $value;
            }
            yield $i + 1 => $row;
        }
    }
}
