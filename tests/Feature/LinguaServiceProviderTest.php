<?php

declare(strict_types=1);

use Illuminate\Translation\Translator;
use Rivalex\Lingua\LinguaServiceProvider;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Services\ExtensionRegistry;
use Rivalex\Lingua\TranslationManager\LinguaManager;

it('provides the expected service names', function (): void {
    $provider = new LinguaServiceProvider(app());

    expect($provider->provides())->toBe(['translator', 'translation.loader', ExtensionRegistry::class]);
});

it('resolves the translation loader as a LinguaManager', function (): void {
    expect(app('translation.loader'))->toBeInstanceOf(LinguaManager::class);
});

it('resolves the translator as a Translator instance', function (): void {
    expect(app('translator'))->toBeInstanceOf(Translator::class);
});

it('sets translator fallback to the default language code when one exists', function (): void {
    // Force re-resolution so the closure runs against current DB state (seeder created 'en')
    app()->forgetInstance('translator');

    expect(app('translator')->getFallback())->toBe('en');
});

it('falls back to config app.locale when no default language is set', function (): void {
    Language::query()->update(['is_default' => false]);
    app()->forgetInstance('translator');
    config(['app.locale' => 'en']);

    expect(app('translator')->getFallback())->toBe('en');
});
