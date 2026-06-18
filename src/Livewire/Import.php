<?php

declare(strict_types=1);

namespace Rivalex\Lingua\Livewire;

use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Transfer\Format\FormatRegistry;
use Rivalex\Lingua\Transfer\ImportCommitService;
use Rivalex\Lingua\Transfer\ImportDiffService;

/**
 * Import child component for the Transfer page.
 *
 * Uses Livewire WithFileUploads for temporary file handling.
 * preview() runs a dry-run; confirm() re-parses and commits.
 * Only summary counts and capped row lists are held in state.
 */
final class Import extends Component
{
    use WithFileUploads;

    /** Uploaded translation file (temporary). */
    #[Validate('nullable|file|max:5120|mimes:csv,txt,json,xlsx,ods')]
    public ?TemporaryUploadedFile $file = null;

    /** Target locale for import. */
    public string $targetLocale = '';

    /** Whether to update existing vendor translation values. */
    public bool $vendorUpdateEnabled = false;

    /** Whether a preview has been run and results are shown. */
    public bool $previewed = false;

    // Summary counts (held in state, not the full diff object)
    public int $createCount = 0;

    public int $updateCount = 0;

    public int $skipCount = 0;

    public int $errorCount = 0;

    /** @var list<array{key: string, action: string}> */
    public array $changes = [];

    /** @var list<array{key: string, reason: string}> */
    public array $skipped = [];

    /** @var list<array{key: string, reason: string}> */
    public array $errors = [];

    /** Error message shown when validation or processing fails. */
    public ?string $errorMessage = null;

    /** Success message shown after a successful commit. */
    public ?string $successMessage = null;

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

    // ── Actions ───────────────────────────────────────────────────────────────

    /**
     * Run a dry-run preview of the import file.
     *
     * Validates the upload, detects format from extension,
     * calls ImportDiffService, and stores counts + capped lists.
     */
    public function preview(): void
    {
        $this->errorMessage = null;
        $this->successMessage = null;
        $this->previewed = false;

        $this->validate();

        if ($this->file === null) {
            $this->errorMessage = 'Please select a file to import.';

            return;
        }

        if ($this->targetLocale === '') {
            $this->errorMessage = 'Please select a target locale.';

            return;
        }

        $format = $this->detectFormat();
        if ($format === null) {
            $this->errorMessage = 'Unsupported file format. Please upload a CSV, JSON, XLSX, or ODS file.';

            return;
        }

        try {
            $diff = app(ImportDiffService::class)->diff(
                filePath: $this->file->getRealPath(),
                format: $format,
                targetLocale: $this->targetLocale,
                vendorUpdateEnabled: $this->vendorUpdateEnabled,
            );

            $this->createCount = $diff->createCount;
            $this->updateCount = $diff->updateCount;
            $this->skipCount = $diff->skipCount;
            $this->errorCount = $diff->errorCount;
            $this->changes = $diff->changes;
            $this->skipped = $diff->skipped;
            $this->errors = $diff->errors;
            $this->previewed = true;
        } catch (\Throwable $e) {
            $this->errorMessage = 'Failed to preview file: '.$e->getMessage();
        }
    }

    /**
     * Confirm and commit the import after a successful preview.
     *
     * Re-parses the file (re-derives the diff) and applies all changes.
     */
    public function confirm(): void
    {
        $this->errorMessage = null;

        if (! $this->previewed || $this->file === null) {
            $this->errorMessage = 'Please preview the file before confirming.';

            return;
        }

        $realPath = $this->file->getRealPath();

        if (! $realPath || ! file_exists($realPath)) {
            $this->errorMessage = 'The uploaded file is no longer available. Please re-upload and preview again.';
            $this->previewed = false;

            return;
        }

        $format = $this->detectFormat();
        if ($format === null) {
            $this->errorMessage = 'Unsupported file format.';

            return;
        }

        try {
            $diff = app(ImportCommitService::class)->commit(
                filePath: $realPath,
                format: $format,
                targetLocale: $this->targetLocale,
                vendorUpdateEnabled: $this->vendorUpdateEnabled,
            );

            $created = $diff->createCount;
            $updated = $diff->updateCount;
            $this->successMessage = "Import complete: {$created} created, {$updated} updated.";

            $this->resetPreview();
        } catch (\Throwable $e) {
            $this->errorMessage = 'Import failed: '.$e->getMessage();
        }
    }

    /**
     * Cancel the current preview and reset all state.
     */
    public function resetImport(): void
    {
        $this->resetPreview();
        $this->errorMessage = null;
        $this->successMessage = null;
        $this->targetLocale = '';
        $this->vendorUpdateEnabled = false;
        $this->file = null;
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render(): View
    {
        return view('lingua::import');
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Detect the import format from the uploaded file extension.
     *
     * Returns null for unsupported/missing formats.
     */
    private function detectFormat(): ?string
    {
        if ($this->file === null) {
            return null;
        }

        $extension = strtolower($this->file->getClientOriginalExtension());

        $available = app(FormatRegistry::class)->availableFormats();

        // txt files are treated as CSV (common export from Excel)
        if ($extension === 'txt') {
            return 'csv';
        }

        return array_key_exists($extension, $available) ? $extension : null;
    }

    /**
     * Reset preview state only (keeps file + locale + vendorUpdateEnabled).
     */
    private function resetPreview(): void
    {
        $this->previewed = false;
        $this->createCount = 0;
        $this->updateCount = 0;
        $this->skipCount = 0;
        $this->errorCount = 0;
        $this->changes = [];
        $this->skipped = [];
        $this->errors = [];
    }
}
