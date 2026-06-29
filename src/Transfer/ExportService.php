<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Transfer;

use Illuminate\Support\Collection;
use Rivalex\Lingua\Contracts\TranslationRepository;
use Rivalex\Lingua\Support\TranslationLine;
use Rivalex\Lingua\Transfer\Enums\TransferFilter;
use Rivalex\Lingua\Transfer\Enums\TransferScope;
use Rivalex\Lingua\Transfer\Format\FormatRegistry;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Assembles a translation export as a streamed HTTP download.
 *
 * All row logic is delegated to RowMapper; all I/O to FormatRegistry writers.
 * This class is the only place that touches the repository for export purposes.
 */
final class ExportService
{
    public function __construct(
        private readonly TranslationRepository $repository,
        private readonly FormatRegistry $registry,
        private readonly RowMapper $mapper,
    ) {}

    /**
     * Build and stream a translation export file.
     *
     * @param  array<int, string>  $allLocaleCodes  Installed locale codes (for multiLocale scope).
     */
    public function export(
        TransferScope $scope,
        TransferFilter $filter,
        string $format,
        string $defaultLocale,
        ?string $targetLocale,
        bool $includeVendor,
        array $allLocaleCodes,
    ): StreamedResponse {
        $writer = $this->registry->writer($format);
        $headers = TransferSchema::buildHeaders($defaultLocale, $targetLocale, $allLocaleCodes, $scope);

        $date = date('Ymd');
        $localePart = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($targetLocale ?? 'all')) ?: 'all';
        $filename = "translations_{$scope->value}_{$localePart}_{$date}.{$writer->extension()}";

        // Write to a temp file then stream it (works for CSV, JSON, and OpenSpout formats)
        $tempPath = tempnam(sys_get_temp_dir(), 'lingua_export_');

        try {
            $lines = $this->repository->all(includeVendor: $includeVendor);

            $rows = $this->buildRows($lines, $defaultLocale, $scope, $targetLocale, $filter, $allLocaleCodes);

            $writer->write($tempPath, $headers, $rows);
        } catch (\Throwable $e) {
            @unlink($tempPath);
            throw $e;
        }

        return new StreamedResponse(function () use ($tempPath): void {
            $handle = fopen($tempPath, 'rb');
            if ($handle !== false) {
                fpassthru($handle);
                fclose($handle);
            }
            @unlink($tempPath);
        }, 200, [
            'Content-Type' => $writer->mimeType(),
            'Content-Disposition' => HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                $filename
            ),
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Build the iterable of row arrays from the collection of TranslationLines.
     *
     * @param  Collection<int, TranslationLine>  $lines
     * @return iterable<array<string, mixed>>
     */
    private function buildRows(
        Collection $lines,
        string $defaultLocale,
        TransferScope $scope,
        ?string $targetLocale,
        TransferFilter $filter,
        array $allLocaleCodes,
    ): iterable {
        foreach ($lines as $line) {
            $row = $this->mapper->lineToRow(
                line: $line,
                defaultLocale: $defaultLocale,
                scope: $scope,
                targetLocale: $targetLocale,
                filter: $filter,
                defaultLocaleCode: $defaultLocale,
            );

            if ($row !== null) {
                yield $row;
            }
        }
    }
}
