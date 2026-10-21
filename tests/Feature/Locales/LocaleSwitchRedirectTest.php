<?php

declare(strict_types=1);

use Livewire\Livewire;
use Rivalex\Lingua\Livewire\HeadlessLanguageSelector;
use Rivalex\Lingua\Livewire\LanguageSelector;
use Rivalex\Lingua\Models\Language;

// ---------------------------------------------------------------------------
// Part A: locale switch reloads current page, not home
// ---------------------------------------------------------------------------

it('changeLocale redirects to the current request URI, not home', function (): void {
    Language::factory()->create(['code' => 'it', 'is_default' => false]);

    // Simulate a request coming in on /lingua/translations?q=hello
    $component = Livewire::test(LanguageSelector::class)
        ->set('currentUrl', '/lingua/translations?q=hello');

    $component->call('changeLocale', 'it')
        ->assertRedirect('/lingua/translations?q=hello');
})->group('locale-switch');

it('currentUrl is a relative path at mount time', function (): void {
    $component = Livewire::test(LanguageSelector::class);

    // In Livewire's test environment request()->getRequestUri() returns '/'.
    // The value must start with '/' and must not contain '://' (no host).
    expect($component->currentUrl)
        ->toStartWith('/')
        ->not->toContain('://');
})->group('locale-switch');

it('changeLocale redirects safely when APP_URL host differs from browsing host', function (): void {
    Language::factory()->create(['code' => 'fr', 'is_default' => false]);

    // Simulate a mismatch: APP_URL=http://localhost, browsing on localhost:8000.
    // Before the fix, url()->current() would be http://localhost:8000/lingua/languages,
    // which parse_url() assigns host=localhost and port=8000 — not equal to 'localhost'
    // from APP_URL, so the guard reset currentUrl to '/'.
    // Now currentUrl is a relative path from getRequestUri(), so the guard never fires.
    config(['app.url' => 'http://localhost']);

    $component = Livewire::test(LanguageSelector::class)
        ->set('currentUrl', '/lingua/languages');

    $component->call('changeLocale', 'fr')
        ->assertRedirect('/lingua/languages');
})->group('locale-switch');

it('open-redirect: protocol-relative URL is neutralised to /', function (): void {
    Language::factory()->create(['code' => 'it', 'is_default' => false]);

    $component = Livewire::test(LanguageSelector::class)
        ->set('currentUrl', '//evil.com/steal');

    $component->call('changeLocale', 'it')
        ->assertRedirect('/');
})->group('locale-switch');

it('open-redirect: absolute https URL is neutralised to /', function (): void {
    Language::factory()->create(['code' => 'it', 'is_default' => false]);

    $component = Livewire::test(LanguageSelector::class)
        ->set('currentUrl', 'https://evil.com/steal');

    $component->call('changeLocale', 'it')
        ->assertRedirect('/');
})->group('locale-switch');

it('open-redirect: backslash-relative URL is neutralised to /', function (): void {
    Language::factory()->create(['code' => 'it', 'is_default' => false]);

    $component = Livewire::test(LanguageSelector::class)
        ->set('currentUrl', '/\\evil.com');

    $component->call('changeLocale', 'it')
        ->assertRedirect('/');
})->group('locale-switch');

it('open-redirect: javascript scheme is neutralised to /', function (): void {
    Language::factory()->create(['code' => 'it', 'is_default' => false]);

    $component = Livewire::test(LanguageSelector::class)
        ->set('currentUrl', 'javascript:alert(1)');

    $component->call('changeLocale', 'it')
        ->assertRedirect('/');
})->group('locale-switch');

it('session and app locale are updated on changeLocale', function (): void {
    Language::factory()->create(['code' => 'de', 'is_default' => false]);

    Livewire::test(LanguageSelector::class)
        ->call('changeLocale', 'de');

    expect(session(config('lingua.session_variable')))->toBe('de')
        ->and(app()->getLocale())->toBe('de');
})->group('locale-switch');

// ---------------------------------------------------------------------------
// Headless selector — same guard logic via ManagesLocale trait
// ---------------------------------------------------------------------------

it('headless selector: changeLocale redirects to current path', function (): void {
    Language::factory()->create(['code' => 'es', 'regional' => 'ES', 'is_default' => false, 'sort' => 2]);

    $component = Livewire::test(HeadlessLanguageSelector::class)
        ->set('currentUrl', '/my/page?ref=sidebar');

    $component->call('changeLocale', 'es')
        ->assertRedirect('/my/page?ref=sidebar');
})->group('locale-switch');

it('headless selector: open-redirect is neutralised', function (): void {
    Language::factory()->create(['code' => 'es', 'regional' => 'ES', 'is_default' => false, 'sort' => 2]);

    $component = Livewire::test(HeadlessLanguageSelector::class)
        ->set('currentUrl', '//evil.com');

    $component->call('changeLocale', 'es')
        ->assertRedirect('/');
})->group('locale-switch');
