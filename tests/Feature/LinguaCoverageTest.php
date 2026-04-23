<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\App;
use Rivalex\Lingua\Facades\Lingua;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

// ---------------------------------------------------------------------------
// getLocale
// ---------------------------------------------------------------------------

it('getLocale returns the current application locale', function (): void {
    App::setLocale('en');

    expect(Lingua::getLocale())->toBe('en');
});

// ---------------------------------------------------------------------------
// isDefaultLocale
// ---------------------------------------------------------------------------

it('isDefaultLocale returns true for the default locale', function (): void {
    expect(Lingua::isDefaultLocale('en'))->toBeTrue();
});

it('isDefaultLocale returns false for a locale that is not default', function (): void {
    expect(Lingua::isDefaultLocale('fr'))->toBeFalse();
});

it('isDefaultLocale uses the current locale when no argument is passed', function (): void {
    App::setLocale('en');

    expect(Lingua::isDefaultLocale())->toBeTrue();
});

// ---------------------------------------------------------------------------
// getLocaleName / getLocaleNative / getDirection
// ---------------------------------------------------------------------------

it('getLocaleName returns a non-empty string for an installed locale', function (): void {
    expect(Lingua::getLocaleName('en'))->toBeString()->not->toBeEmpty();
});

it('getLocaleName returns an empty string for an unknown locale', function (): void {
    expect(Lingua::getLocaleName('xx_UNKNOWN'))->toBe('');
});

it('getLocaleName uses the current locale when no argument is passed', function (): void {
    App::setLocale('en');

    expect(Lingua::getLocaleName())->toBeString()->not->toBeEmpty();
});

it('getLocaleNative returns a string for an installed locale', function (): void {
    expect(Lingua::getLocaleNative('en'))->toBeString();
});

it('getLocaleNative uses the current locale when no argument is passed', function (): void {
    App::setLocale('en');

    expect(Lingua::getLocaleNative())->toBeString();
});

it('getDirection returns ltr for English', function (): void {
    expect(Lingua::getDirection('en'))->toBe('ltr');
});

it('getDirection returns ltr as default for unknown locales', function (): void {
    expect(Lingua::getDirection('xx_UNKNOWN'))->toBe('ltr');
});

it('getDirection uses the current locale when no argument is passed', function (): void {
    App::setLocale('en');

    expect(Lingua::getDirection())->toBe('ltr');
});

// ---------------------------------------------------------------------------
// Language getters
// ---------------------------------------------------------------------------

it('get returns the Language model for a known locale', function (): void {
    $lang = Lingua::get('en');

    expect($lang)->toBeInstanceOf(Language::class)
        ->and($lang->code)->toBe('en');
});

it('get returns null for an unknown locale', function (): void {
    expect(Lingua::get('xx_UNKNOWN'))->toBeNull();
});

it('getDefault returns the Language model marked as default', function (): void {
    $lang = Lingua::getDefault();

    expect($lang)->toBeInstanceOf(Language::class)
        ->and($lang->is_default)->toBeTrue();
});

it('getFallback returns the Language for the configured fallback locale', function (): void {
    config(['app.fallback_locale' => 'en']);

    expect(Lingua::getFallback())->toBeInstanceOf(Language::class);
});

it('getDefaultLocale returns the default language code', function (): void {
    expect(Lingua::getDefaultLocale())->toBe('en');
});

it('getDefaultLocale falls back to config when no default language row exists', function (): void {
    Language::query()->update(['is_default' => false]);
    config(['lingua.default_locale' => 'en']);

    expect(Lingua::getDefaultLocale())->toBe('en');
});

// ---------------------------------------------------------------------------
// hasLocale / isInstalled / isAvailable
// ---------------------------------------------------------------------------

it('hasLocale returns true for an installed locale', function (): void {
    expect(Lingua::hasLocale('en'))->toBeTrue();
});

it('hasLocale returns false for an unrecognised locale', function (): void {
    expect(Lingua::hasLocale('xx_FAKE999'))->toBeFalse();
});

it('isInstalled returns true for an installed locale', function (): void {
    expect(Lingua::isInstalled('en'))->toBeTrue();
});

it('isInstalled returns false for a non-installed locale', function (): void {
    expect(Lingua::isInstalled('de'))->toBeFalse();
});

it('isInstalled returns false when passed null', function (): void {
    expect(Lingua::isInstalled(null))->toBeFalse();
});

it('available returns an array', function (): void {
    expect(Lingua::available())->toBeArray();
});

it('isAvailable returns false for null', function (): void {
    expect(Lingua::isAvailable(null))->toBeFalse();
});

it('isAvailable returns false for an already-installed locale', function (): void {
    // 'en' is installed, therefore NOT in notInstalled()
    expect(Lingua::isAvailable('en'))->toBeFalse();
});

// ---------------------------------------------------------------------------
// setDefaultLocale
// ---------------------------------------------------------------------------

it('setDefaultLocale throws ModelNotFoundException for an unknown locale', function (): void {
    expect(fn () => Lingua::setDefaultLocale('xx_FAKE999'))
        ->toThrow(ModelNotFoundException::class);
});

it('setDefaultLocale marks the given language as default', function (): void {
    Language::factory()->create([
        'code' => 'fr', 'regional' => 'FR', 'type' => 'locale',
        'name' => 'French', 'native' => 'Français', 'direction' => 'ltr',
        'is_default' => false, 'sort' => 50,
    ]);

    Lingua::setDefaultLocale('fr');

    expect(Language::where('code', 'fr')->first()->is_default)->toBeTrue();
});

// ---------------------------------------------------------------------------
// translations / getTranslations / getTranslation
// ---------------------------------------------------------------------------

it('translations returns a collection', function (): void {
    expect(Lingua::translations())->toBeInstanceOf(Collection::class);
});

it('getTranslations returns an empty array for an unknown key', function (): void {
    expect(Lingua::getTranslations('cov.nonexistent_key_xyz'))->toBe([]);
});

it('getTranslations returns the text array for a known key', function (): void {
    Translation::create([
        'group' => 'cov', 'key' => 'cov_get_translations', 'type' => 'text',
        'text' => ['en' => 'Hello'], 'is_vendor' => false, 'vendor' => null,
    ]);

    expect(Lingua::getTranslations('cov_get_translations'))->toHaveKey('en');
});

it('getTranslation returns an empty string for an unknown key', function (): void {
    expect(Lingua::getTranslation('cov.nonexistent_xyz', 'en'))->toBe('');
});

it('getTranslation returns the value for a known key and locale', function (): void {
    Translation::create([
        'group' => 'cov', 'key' => 'cov_get_translation', 'type' => 'text',
        'text' => ['en' => 'Hi there'], 'is_vendor' => false, 'vendor' => null,
    ]);

    expect(Lingua::getTranslation('cov_get_translation', 'en'))->toBe('Hi there');
});

// ---------------------------------------------------------------------------
// setTranslation
// ---------------------------------------------------------------------------

it('setTranslation is a no-op when the key does not exist', function (): void {
    Lingua::setTranslation('cov.nonexistent_xyz', 'value', 'en');

    expect(true)->toBeTrue(); // no exception thrown
});

it('setTranslation updates the translation for an existing key', function (): void {
    Translation::create([
        'group' => 'cov', 'key' => 'cov_set_translation', 'type' => 'text',
        'text' => ['en' => 'original'], 'is_vendor' => false, 'vendor' => null,
    ]);

    Lingua::setTranslation('cov_set_translation', 'updated', 'en');

    expect(Lingua::getTranslation('cov_set_translation', 'en'))->toBe('updated');
});

// ---------------------------------------------------------------------------
// forgetTranslation
// ---------------------------------------------------------------------------

it('forgetTranslation is a no-op when the key does not exist', function (): void {
    Lingua::forgetTranslation('cov.nonexistent_xyz', 'fr');

    expect(true)->toBeTrue();
});

it('forgetTranslation on a non-default locale removes that locale from the text array', function (): void {
    Language::factory()->create([
        'code' => 'fr', 'regional' => 'FR', 'type' => 'locale',
        'name' => 'French', 'native' => 'Français', 'direction' => 'ltr',
        'is_default' => false, 'sort' => 50,
    ]);
    Translation::create([
        'group' => 'cov', 'key' => 'cov_forget', 'type' => 'text',
        'text' => ['en' => 'Hello', 'fr' => 'Bonjour'], 'is_vendor' => false, 'vendor' => null,
    ]);

    Lingua::forgetTranslation('cov_forget', 'fr');

    expect(Lingua::getTranslations('cov_forget'))->not->toHaveKey('fr');
});

// ---------------------------------------------------------------------------
// getTranslationByGroup
// ---------------------------------------------------------------------------

it('getTranslationByGroup returns translations in the given group', function (): void {
    Translation::create([
        'group' => 'cov_grp', 'key' => 'cov_grp_key', 'type' => 'text',
        'text' => ['en' => 'val'], 'is_vendor' => false, 'vendor' => null,
    ]);

    expect(Lingua::getTranslationByGroup('cov_grp'))->not->toBeEmpty();
});

it('getTranslationByGroup with locale filters to only translations that have that locale', function (): void {
    Translation::create([
        'group' => 'cov_grpf', 'key' => 'cov_grpf_key', 'type' => 'text',
        'text' => ['en' => 'yes'], 'is_vendor' => false, 'vendor' => null,
    ]);

    expect(Lingua::getTranslationByGroup('cov_grpf', 'en'))->not->toBeEmpty();
});

// ---------------------------------------------------------------------------
// Statistics
// ---------------------------------------------------------------------------

it('getLocaleStats returns an array', function (): void {
    expect(Lingua::getLocaleStats('en'))->toBeArray();
});

it('getLocaleStats uses the current locale when no argument is passed', function (): void {
    App::setLocale('en');

    expect(Lingua::getLocaleStats())->toBeArray();
});

it('languagesWithStatistics returns a collection', function (): void {
    expect(Lingua::languagesWithStatistics())->toBeInstanceOf(Collection::class);
});

// ---------------------------------------------------------------------------
// Vendor translations
// ---------------------------------------------------------------------------

it('getVendorTranslations returns empty for an unknown vendor', function (): void {
    expect(Lingua::getVendorTranslations('cov-unknown-vendor'))->toBeEmpty();
});

it('getVendorTranslations returns records for a known vendor', function (): void {
    Translation::create([
        'group' => 'messages', 'key' => 'cov_vendor_hello', 'type' => 'text',
        'text' => ['en' => 'Hello'], 'is_vendor' => true, 'vendor' => 'cov-pkg',
    ]);

    expect(Lingua::getVendorTranslations('cov-pkg'))->not->toBeEmpty();
});

it('setVendorTranslation throws ModelNotFoundException for an unknown key', function (): void {
    expect(fn () => Lingua::setVendorTranslation('cov-no-vendor', 'group', 'key', 'value', 'en'))
        ->toThrow(ModelNotFoundException::class);
});

it('setVendorTranslation saves the value for a known vendor key', function (): void {
    Translation::create([
        'group' => 'messages', 'key' => 'cov_vendor_set', 'type' => 'text',
        'text' => ['en' => 'original'], 'is_vendor' => true, 'vendor' => 'cov-vendor',
    ]);

    Lingua::setVendorTranslation('cov-vendor', 'messages', 'cov_vendor_set', 'updated', 'en');

    expect(Translation::where('key', 'cov_vendor_set')->first()->text['en'])->toBe('updated');
});

// ---------------------------------------------------------------------------
// updateLanguages — early-return path (empty DB)
// ---------------------------------------------------------------------------

it('updateLanguages returns early without error when no languages are installed', function (): void {
    Language::query()->delete();

    Lingua::updateLanguages(); // must not throw

    expect(true)->toBeTrue();
});
