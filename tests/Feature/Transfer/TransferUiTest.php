<?php

declare(strict_types=1);

use Livewire\Livewire;
use Rivalex\Lingua\Livewire\Export;
use Rivalex\Lingua\Livewire\Import;
use Rivalex\Lingua\Livewire\Transfer;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Transfer\Format\FormatRegistry;
use Rivalex\Lingua\Transfer\SpreadsheetSupport;

// ── Navigation smoke tests (Livewire::test renders real Blade) ────────────────
//
// These tests compile the full Blade output, so property-name collisions
// (e.g. $errors shadowing ViewErrorBag) and missing component registrations
// are caught here rather than at runtime in the browser.

test('Transfer page renders without errors', function (): void {
    Livewire::test(Transfer::class)->assertOk();
});

test('Export component renders without errors', function (): void {
    Livewire::test(Export::class)->assertOk();
});

test('Import component renders without errors', function (): void {
    // Covers the @error('file') directive that previously crashed due to
    // public array $errors shadowing the ViewErrorBag.
    Livewire::test(Import::class)->assertOk();
});

test('Import component initial form is visible', function (): void {
    // previewed=false by default → upload form section is rendered.
    Livewire::test(Import::class)
        ->assertSet('previewed', false)
        ->assertSet('targetLocale', '')
        ->assertOk();
});

// ── Export download route ─────────────────────────────────────────────────────

test('export download route returns a streamed response for csv', function (): void {
    // Use Language factory so all required fields are set correctly
    Language::factory()->create(['code' => 'it', 'is_default' => false]);

    $response = $this->withoutMiddleware()->get(route('lingua.transfer.export', [
        'scope' => 'bilingual',
        'filter' => 'all',
        'format' => 'csv',
        'targetLocale' => 'it',
        'includeVendor' => '0',
    ]));

    $response->assertStatus(200);
    // Content-Type may include charset suffix; use assertHeader with contains check
    $contentType = $response->headers->get('Content-Type', '');
    expect($contentType)->toContain('text/csv');
    $response->assertHeader('Content-Disposition');
});

test('export download route returns 422 for invalid scope', function (): void {
    $response = $this->withoutMiddleware()->get(route('lingua.transfer.export', [
        'scope' => 'invalid_scope',
        'filter' => 'all',
        'format' => 'csv',
        'targetLocale' => 'it',
    ]));

    $response->assertStatus(422);
});

test('export download route returns 422 when bilingual scope missing target locale', function (): void {
    $response = $this->withoutMiddleware()->get(route('lingua.transfer.export', [
        'scope' => 'bilingual',
        'filter' => 'all',
        'format' => 'csv',
        'targetLocale' => '',
    ]));

    $response->assertStatus(422);
});

// ── Export component state / validation ──────────────────────────────────────

test('export component has correct initial state', function (): void {
    $component = app()->make(Export::class);
    expect($component->scope)->toBe('bilingual')
        ->and($component->filter)->toBe('all')
        ->and($component->format)->toBe('csv')
        ->and($component->targetLocale)->toBe('')
        ->and($component->includeVendor)->toBeFalse();
});

test('export service is available via app container', function (): void {
    $registry = app(FormatRegistry::class);
    $formats = $registry->availableFormats();
    expect($formats)->toHaveKey('csv')->toHaveKey('json');
});

// ── Import component state ────────────────────────────────────────────────────

test('import component has correct initial state', function (): void {
    $component = app()->make(Import::class);
    expect($component->targetLocale)->toBe('')
        ->and($component->vendorUpdateEnabled)->toBeFalse()
        ->and($component->previewed)->toBeFalse()
        ->and($component->createCount)->toBe(0);
});

// ── Format registry degradation ───────────────────────────────────────────────

test('format registry always exposes csv and json', function (): void {
    $registry = new FormatRegistry;
    $formats = $registry->availableFormats();
    expect($formats)->toHaveKey('csv')->toHaveKey('json');
});

test('format registry shows xlsx and ods only when openspout available', function (): void {
    $registry = new FormatRegistry;
    $formats = $registry->availableFormats();

    if (SpreadsheetSupport::available()) {
        expect($formats)->toHaveKey('xlsx')->toHaveKey('ods');
    } else {
        expect($formats)->not->toHaveKey('xlsx')->not->toHaveKey('ods');
    }
});

// ── Transfer route is registered ─────────────────────────────────────────────

test('lingua.transfer route is registered', function (): void {
    expect(route('lingua.transfer'))->toContain('transfer');
});

test('lingua.transfer.export route is registered', function (): void {
    expect(route('lingua.transfer.export'))->toContain('transfer/export');
});
