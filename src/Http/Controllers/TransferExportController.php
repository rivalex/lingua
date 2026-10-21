<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Http\Controllers;

use Illuminate\Http\Request;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Transfer\Enums\TransferFilter;
use Rivalex\Lingua\Transfer\Enums\TransferScope;
use Rivalex\Lingua\Transfer\ExportService;
use Rivalex\Lingua\Transfer\Format\FormatRegistry;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Handles translation export file downloads.
 *
 * This is a plain Laravel controller (not Livewire) so that large streamed
 * exports work reliably outside the Livewire request cycle. The Livewire
 * Export component redirects here after validation.
 */
final class TransferExportController
{
    /**
     * Stream a translation export file for download.
     *
     * @throws HttpException on invalid params
     */
    public function download(Request $request): StreamedResponse
    {
        $scopeValue = $request->string('scope', 'bilingual')->toString();
        $filterValue = $request->string('filter', 'all')->toString();
        $format = $request->string('format', 'csv')->toString();
        $targetLocale = $request->string('targetLocale', '')->toString();
        $includeVendor = $request->boolean('includeVendor', false);

        $scope = TransferScope::tryFrom($scopeValue);
        $filter = TransferFilter::tryFrom($filterValue);

        abort_if($scope === null, 422, 'Invalid export scope.');
        abort_if($filter === null, 422, 'Invalid export filter.');

        // Validate format is available
        $available = app(FormatRegistry::class)->availableFormats();
        abort_unless(array_key_exists($format, $available), 422, 'Unavailable export format.');

        // Target locale required for bilingual scope
        if ($scope === TransferScope::bilingual) {
            abort_if($targetLocale === '', 422, 'Target locale is required for bilingual export.');

            $installed = Language::pluck('code');
            abort_unless($installed->contains($targetLocale), 422, 'Unknown target locale.');
        }

        $allLocaleCodes = Language::orderBy('sort')
            ->orderBy('name')
            ->pluck('code')
            ->all();

        $defaultLocale = linguaDefaultLocale();

        return app(ExportService::class)->export(
            scope: $scope,
            filter: $filter,
            format: $format,
            defaultLocale: $defaultLocale,
            targetLocale: $targetLocale !== '' ? $targetLocale : null,
            includeVendor: $includeVendor,
            allLocaleCodes: $allLocaleCodes,
        );
    }
}
