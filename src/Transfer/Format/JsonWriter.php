<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Transfer\Format;

/**
 * Writes translation rows to a JSON file (lossless native format).
 *
 * The $headers argument is ignored — JSON output encodes the full row array
 * as received from ExportService (pre-built by RowMapper::buildJsonNativeRow).
 */
final class JsonWriter implements FormatWriter
{
    /**
     * @param  list<string>  $headers  Ignored for JSON format.
     * @param  iterable<array<string, mixed>>  $rows
     */
    public function write(string $path, array $headers, iterable $rows): void
    {
        $data = [];
        foreach ($rows as $row) {
            $data[] = $row;
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        if (file_put_contents($path, $json) === false) {
            throw new \RuntimeException("Cannot write JSON file: {$path}");
        }
    }

    public function mimeType(): string
    {
        return 'application/json';
    }

    public function extension(): string
    {
        return 'json';
    }
}
