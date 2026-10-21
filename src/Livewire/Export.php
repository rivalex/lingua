<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Livewire;

use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Transfer\Enums\TransferFilter;
use Rivalex\Lingua\Transfer\Enums\TransferScope;
use Rivalex\Lingua\Transfer\Format\FormatRegistry;
use Rivalex\Lingua\Transfer\SpreadsheetSupport;

/**
 * Export child component for the Transfer page.
 *
 * Holds the export form state and redirects to the dedicated download
 * route (TransferExportController) which streams the file. Livewire
 * wire:download is avoided for reliability with large files.
 */
final class Export extends Component
{
    /** Export scope: bilingual | multi_locale | json_native */
    public string $scope = 'bilingual';

    /** Filter: all | only_missing */
    public string $filter = 'all';

    /** Format: csv | json | xlsx | ods */
    public string $format = 'csv';

    /** Target locale code (required for bilingual scope). */
    public string $targetLocale = '';

    /** Whether to include vendor translations in the export. */
    public bool $includeVendor = false;

    /** Validation error message (if any). */
    public ?string $errorMessage = null;

    // ── Computed ─────────────────────────────────────────────────────────────

    /**
     * Active languages for the target locale selector.
     *
     * @return Collection<int, Language>
     */
    #[Computed]
    public function languages(): Collection
    {
        return Language::orderBy('sort')->orderBy('name')->get();
    }

    /**
     * Available export formats from the format registry.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function availableFormats(): array
    {
        return app(FormatRegistry::class)->availableFormats();
    }

    /**
     * Whether OpenSpout is available (used for UI hint).
     */
    #[Computed]
    public function spreadsheetAvailable(): bool
    {
        return SpreadsheetSupport::available();
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    /**
     * Validate form state and redirect to the download route.
     *
     * The download route streams the file via ExportService — this keeps
     * large exports outside the Livewire request cycle.
     */
    public function export(): void
    {
        $this->errorMessage = null;

        // Validate scope
        if (TransferScope::tryFrom($this->scope) === null) {
            $this->errorMessage = 'Invalid scope selected.';

            return;
        }

        // Validate filter
        if (TransferFilter::tryFrom($this->filter) === null) {
            $this->errorMessage = 'Invalid filter selected.';

            return;
        }

        // Validate format
        $availableFormats = app(FormatRegistry::class)->availableFormats();
        if (! array_key_exists($this->format, $availableFormats)) {
            $this->errorMessage = 'Invalid or unavailable format selected.';

            return;
        }

        // Target locale required for bilingual scope
        if ($this->scope === TransferScope::bilingual->value && $this->targetLocale === '') {
            $this->errorMessage = 'Please select a target locale for bilingual export.';

            return;
        }

        $this->redirect(route('lingua.transfer.export', [
            'scope' => $this->scope,
            'filter' => $this->filter,
            'format' => $this->format,
            'targetLocale' => $this->targetLocale,
            'includeVendor' => $this->includeVendor ? '1' : '0',
        ]), navigate: false);
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render(): View
    {
        return view('lingua::export');
    }
}
