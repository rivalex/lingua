<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Transfer\Format;

use Rivalex\Lingua\Transfer\SpreadsheetSupport;

/**
 * Maps format string keys to their writer/reader implementations.
 *
 * XLSX and ODS formats are only available when openspout/openspout is installed.
 * Callers should check availableFormats() before constructing a writer/reader.
 */
final class FormatRegistry
{
    /**
     * Returns the list of available export formats as [key => label].
     *
     * @return array<string, string>
     */
    public function availableFormats(): array
    {
        $formats = [
            'csv' => 'CSV',
            'json' => 'JSON (native lossless)',
        ];

        if (SpreadsheetSupport::available()) {
            $formats['xlsx'] = 'Excel (XLSX)';
            $formats['ods'] = 'OpenDocument (ODS)';
        }

        return $formats;
    }

    /**
     * Return the FormatWriter for the given format key.
     *
     * @throws SpreadsheetUnavailableException when xlsx/ods requested without OpenSpout.
     * @throws \InvalidArgumentException for unknown format keys.
     */
    public function writer(string $format): FormatWriter
    {
        return match ($format) {
            'csv' => new CsvWriter,
            'json' => new JsonWriter,
            'xlsx', 'ods' => $this->spreadsheetWriter($format),
            default => throw new \InvalidArgumentException("Unknown export format: '{$format}'"),
        };
    }

    /**
     * Return the FormatReader for the given format key.
     *
     * @throws SpreadsheetUnavailableException when xlsx/ods requested without OpenSpout.
     * @throws \InvalidArgumentException for unknown format keys.
     */
    public function reader(string $format): FormatReader
    {
        return match ($format) {
            'csv' => new CsvReader,
            'json' => new JsonReader,
            'xlsx', 'ods' => $this->spreadsheetReader($format),
            default => throw new \InvalidArgumentException("Unknown import format: '{$format}'"),
        };
    }

    /**
     * Return a spreadsheet writer, throwing when OpenSpout is absent.
     */
    private function spreadsheetWriter(string $format): FormatWriter
    {
        if (! SpreadsheetSupport::available()) {
            throw new SpreadsheetUnavailableException($format);
        }

        return $format === 'xlsx' ? new XlsxWriter : new OdsWriter;
    }

    /**
     * Return a spreadsheet reader, throwing when OpenSpout is absent.
     */
    private function spreadsheetReader(string $format): FormatReader
    {
        if (! SpreadsheetSupport::available()) {
            throw new SpreadsheetUnavailableException($format);
        }

        return $format === 'xlsx' ? new XlsxReader : new OdsReader;
    }
}
