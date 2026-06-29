<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Rivalex\Lingua\Livewire\Import;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Transfer\Enums\TransferFilter;
use Rivalex\Lingua\Transfer\Enums\TransferScope;
use Rivalex\Lingua\Transfer\ExportService;
use Rivalex\Lingua\Transfer\ImportDiffService;

// ── F4: export filename sanitization ──────────────────────────────────────────

test('F4-a: malicious targetLocale cannot inject into Content-Disposition filename', function (): void {
    $response = app(ExportService::class)->export(
        scope: TransferScope::bilingual,
        filter: TransferFilter::all,
        format: 'csv',
        defaultLocale: 'en',
        targetLocale: 'x";filename="evil.html',
        includeVendor: false,
        allLocaleCodes: ['en'],
    );

    $disposition = $response->headers->get('Content-Disposition');

    expect($disposition)->not->toContain('evil.html')
        ->and($disposition)->not->toContain('";')
        ->and($disposition)->toContain('translations_bilingual_xfilenameevilhtml_');
});

// ── F4: export controller rejects unknown target locale ───────────────────────

test('F4-b: export route returns 422 for an unknown target locale', function (): void {
    // No Language with this code is installed.
    $response = $this->withoutMiddleware()->get(route('lingua.transfer.export', [
        'scope' => 'bilingual',
        'filter' => 'all',
        'format' => 'csv',
        'targetLocale' => 'not_a_real_locale',
    ]));

    $response->assertStatus(422);
});

// ── F8: import preview does not leak exception messages ───────────────────────

test('F8: import preview failure shows a generic localized message, not the raw exception', function (): void {
    Language::factory()->create(['code' => 'it', 'is_default' => false]);

    app()->bind(ImportDiffService::class, fn () => new class
    {
        public function diff(...$args): never
        {
            throw new RuntimeException('SENSITIVE /var/secret/internal/path.php leaked');
        }
    });

    $file = UploadedFile::fake()->createWithContent('import.csv', "key,en,it\nfoo,Foo,\n");

    Livewire::test(Import::class)
        ->set('targetLocale', 'it')
        ->set('file', $file)
        ->call('preview')
        ->assertSet('errorMessage', __('lingua::lingua.transfer.import_preview_error'))
        ->assertSet('previewed', false);

    expect(__('lingua::lingua.transfer.import_preview_error'))
        ->not->toContain('SENSITIVE')
        ->not->toContain('/var/secret');
});
