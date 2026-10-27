<?php

declare(strict_types=1);

use Livewire\Livewire;
use Rivalex\Lingua\Livewire\HeadlessLanguageSelector;
use Rivalex\Lingua\Models\Language;

// ---------------------------------------------------------------------------
// CSS violation guard
// ---------------------------------------------------------------------------

it('contains no CSS classes in rendered output', function (): void {
    $html = Livewire::test(HeadlessLanguageSelector::class)->html();

    expect($html)->not->toContain('class=');
})->group('headless-selector');

// ---------------------------------------------------------------------------
// Basic rendering
// ---------------------------------------------------------------------------

it('renders the headless selector', function (): void {
    Livewire::test(HeadlessLanguageSelector::class)
        ->assertOk()
        ->assertSeeHtml('data-lingua-selector');
})->group('headless-selector');

it('renders all active languages', function (): void {
    Language::factory()->create([
        'code' => 'it', 'regional' => 'IT', 'type' => 'locale',
        'name' => 'Italian', 'native' => 'Italiano', 'direction' => 'ltr',
        'is_default' => false, 'sort' => 2,
    ]);

    Livewire::test(HeadlessLanguageSelector::class)
        ->assertSeeHtml('data-lingua-item')
        ->assertSee('English')
        ->assertSee('Italian');
})->group('headless-selector');

it('does not render any Flux components', function (): void {
    $html = Livewire::test(HeadlessLanguageSelector::class)->html();

    expect($html)
        ->not->toContain('data-flux-')
        ->not->toContain('<flux:')
        ->not->toContain('x-data=');
})->group('headless-selector');

// ---------------------------------------------------------------------------
// ARIA and data attributes
// ---------------------------------------------------------------------------

it('exposes data-lingua-selector on the root element', function (): void {
    Livewire::test(HeadlessLanguageSelector::class)
        ->assertSeeHtml('data-lingua-selector');
})->group('headless-selector');

it('exposes data-lingua-item on each language element', function (): void {
    Language::factory()->create(['code' => 'it', 'regional' => 'IT', 'is_default' => false, 'sort' => 2]);

    $html = Livewire::test(HeadlessLanguageSelector::class)->html();

    expect(substr_count($html, 'data-lingua-item'))->toBe(2);
})->group('headless-selector');

it('marks the current locale with data-lingua-active', function (): void {
    Livewire::test(HeadlessLanguageSelector::class)
        ->assertSeeHtml('data-lingua-active');
})->group('headless-selector');

it('sets aria-current="true" on the active language', function (): void {
    Livewire::test(HeadlessLanguageSelector::class)
        ->assertSeeHtml('aria-current="true"');
})->group('headless-selector');

it('does not set aria-current on inactive languages', function (): void {
    Language::factory()->create(['code' => 'it', 'regional' => 'IT', 'is_default' => false, 'sort' => 2]);

    $html = Livewire::test(HeadlessLanguageSelector::class)->html();

    expect(substr_count($html, 'aria-current="true"'))->toBe(1);
})->group('headless-selector');

it('includes role="navigation" on the root element', function (): void {
    Livewire::test(HeadlessLanguageSelector::class)
        ->assertSeeHtml('role="navigation"');
})->group('headless-selector');

it('includes aria-label on the nav element', function (): void {
    Livewire::test(HeadlessLanguageSelector::class)
        ->assertSeeHtml('aria-label=');
})->group('headless-selector');

// ---------------------------------------------------------------------------
// Default slot content
// ---------------------------------------------------------------------------

it('renders usably without any slots provided', function (): void {
    Livewire::test(HeadlessLanguageSelector::class)
        ->assertSeeHtml('data-lingua-name')
        ->assertSeeHtml('data-lingua-native')
        ->assertSeeHtml('data-lingua-code')
        ->assertSeeHtml('data-lingua-button');
})->group('headless-selector');

// ---------------------------------------------------------------------------
// Locale switching
// ---------------------------------------------------------------------------

it('calls changeLocale and redirects to current url', function (): void {
    Language::factory()->create(['code' => 'it', 'regional' => 'IT', 'is_default' => false, 'sort' => 2]);

    $component = Livewire::test(HeadlessLanguageSelector::class);
    $redirect = $component->currentUrl;

    $component->call('changeLocale', 'it')
        ->assertRedirect($redirect);

    expect(session('locale'))->toBe('it')
        ->and(app()->getLocale())->toBe('it');
})->group('headless-selector');

it('ignores changeLocale call for unknown locale', function (): void {
    Livewire::test(HeadlessLanguageSelector::class)
        ->call('changeLocale', 'xx_FAKE')
        ->assertNoRedirect();

    expect(session('locale'))->not->toBe('xx_FAKE');
})->group('headless-selector');
