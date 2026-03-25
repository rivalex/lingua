<?php

use Rivalex\Lingua\Facades\Lingua;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeFacadeTranslation(string $group = 'facade_test', ?string $key = null, array $text = ['en' => 'Hello']): Translation
{
    return Translation::create([
        'group'     => $group,
        'key'       => $key ?? 'key_'.uniqid(),
        'type'      => 'text',
        'text'      => $text,
        'is_vendor' => false,
        'vendor'    => null,
    ]);
}

// ---------------------------------------------------------------------------
// getLocale
// ---------------------------------------------------------------------------

it('getLocale returns the current app locale', function () {
    app()->setLocale('en');
    expect(Lingua::getLocale())->toBe('en');
});

it('getLocale reflects locale changes', function () {
    app()->setLocale('fr');
    expect(Lingua::getLocale())->toBe('fr');
    app()->setLocale('en');
});

// ---------------------------------------------------------------------------
// getDefaultLocale
// ---------------------------------------------------------------------------

it('getDefaultLocale returns the default language code from the database', function () {
    expect(Lingua::getDefaultLocale())->toBe('en');
});

it('getDefaultLocale reflects after setDefaultLocale', function () {
    Language::factory()->create(['code' => 'it', 'is_default' => false]);

    Lingua::setDefaultLocale('it');

    expect(Lingua::getDefaultLocale())->toBe('it');

    // Restore
    Lingua::setDefaultLocale('en');
    Language::where('code', 'it')->delete();
});

// ---------------------------------------------------------------------------
// isDefaultLocale
// ---------------------------------------------------------------------------

it('isDefaultLocale returns true for the default locale', function () {
    expect(Lingua::isDefaultLocale('en'))->toBeTrue();
});

it('isDefaultLocale returns false for a non-default locale', function () {
    Language::factory()->create(['code' => 'de', 'is_default' => false]);
    expect(Lingua::isDefaultLocale('de'))->toBeFalse();
    Language::where('code', 'de')->delete();
});

it('isDefaultLocale uses current app locale when none provided', function () {
    app()->setLocale('en');
    expect(Lingua::isDefaultLocale())->toBeTrue();
});

it('isDefaultLocale returns false for an unknown locale without throwing', function () {
    expect(Lingua::isDefaultLocale('zz_UNKNOWN'))->toBeFalse();
});

// ---------------------------------------------------------------------------
// hasLocale
// ---------------------------------------------------------------------------

it('hasLocale returns true for an installed language', function () {
    expect(Lingua::hasLocale('en'))->toBeTrue();
});

it('hasLocale returns false for an unknown locale', function () {
    expect(Lingua::hasLocale('xx'))->toBeFalse();
});

it('hasLocale finds a newly created language', function () {
    Language::factory()->create(['code' => 'nl', 'is_default' => false]);
    expect(Lingua::hasLocale('nl'))->toBeTrue();
    Language::where('code', 'nl')->delete();
});

// ---------------------------------------------------------------------------
// setDefaultLocale
// ---------------------------------------------------------------------------

it('setDefaultLocale changes the default language', function () {
    Language::factory()->create(['code' => 'es', 'is_default' => false]);

    Lingua::setDefaultLocale('es');

    expect(Language::default()->code)->toBe('es');

    // Restore
    Lingua::setDefaultLocale('en');
    Language::where('code', 'es')->delete();
});

it('setDefaultLocale ensures only one language is default', function () {
    Language::factory()->create(['code' => 'pt', 'is_default' => false]);

    Lingua::setDefaultLocale('pt');

    expect(Language::where('is_default', true)->count())->toBe(1)
        ->and(Language::default()->code)->toBe('pt');

    // Restore
    Lingua::setDefaultLocale('en');
    Language::where('code', 'pt')->delete();
});

// ---------------------------------------------------------------------------
// getLocaleName
// ---------------------------------------------------------------------------

it('getLocaleName returns the English name of the current locale', function () {
    app()->setLocale('en');
    expect(Lingua::getLocaleName())->toBe('English');
});

it('getLocaleName returns the name for an explicit locale', function () {
    Language::factory()->create(['code' => 'fr', 'name' => 'French', 'is_default' => false]);
    expect(Lingua::getLocaleName('fr'))->toBe('French');
    Language::where('code', 'fr')->delete();
});

it('getLocaleName returns empty string for unknown locale', function () {
    expect(Lingua::getLocaleName('zz'))->toBe('');
});

// ---------------------------------------------------------------------------
// getLocaleNative
// ---------------------------------------------------------------------------

it('getLocaleNative returns the native name of the current locale', function () {
    app()->setLocale('en');
    expect(Lingua::getLocaleNative())->toBe('English');
});

it('getLocaleNative returns native name for an explicit locale', function () {
    Language::factory()->create(['code' => 'it', 'native' => 'Italiano', 'is_default' => false]);
    expect(Lingua::getLocaleNative('it'))->toBe('Italiano');
    Language::where('code', 'it')->delete();
});

it('getLocaleNative returns empty string for unknown locale', function () {
    expect(Lingua::getLocaleNative('zz'))->toBe('');
});

// ---------------------------------------------------------------------------
// getDirection
// ---------------------------------------------------------------------------

it('getDirection returns ltr for English', function () {
    app()->setLocale('en');
    expect(Lingua::getDirection())->toBe('ltr');
});

it('getDirection returns ltr for an explicit ltr locale', function () {
    expect(Lingua::getDirection('en'))->toBe('ltr');
});

it('getDirection returns rtl for an rtl locale', function () {
    Language::factory()->create(['code' => 'ar', 'direction' => 'rtl', 'is_default' => false]);
    expect(Lingua::getDirection('ar'))->toBe('rtl');
    Language::where('code', 'ar')->delete();
});

it('getDirection falls back to ltr for unknown locale', function () {
    expect(Lingua::getDirection('zz'))->toBe('ltr');
});

// ---------------------------------------------------------------------------
// languages
// ---------------------------------------------------------------------------

it('languages returns a collection of all installed languages', function () {
    $languages = Lingua::languages();
    expect($languages)->not->toBeEmpty()
        ->and($languages->first())->toBeInstanceOf(\Rivalex\Lingua\Models\Language::class);
});

it('languages collection contains the default language', function () {
    $codes = Lingua::languages()->pluck('code');
    expect($codes)->toContain('en');
});

// ---------------------------------------------------------------------------
// languagesWithStatistics
// ---------------------------------------------------------------------------

it('languagesWithStatistics returns languages with computed stats', function () {
    $languages = Lingua::languagesWithStatistics();

    expect($languages)->not->toBeEmpty();

    $lang = $languages->firstWhere('code', 'en');
    expect($lang)->not->toBeNull()
        ->and($lang->total_strings)->toBeInt()
        ->and($lang->translated_strings)->toBeInt()
        ->and($lang->missing_strings)->toBeInt()
        ->and($lang->completion_percentage)->toBeFloat();
});

// ---------------------------------------------------------------------------
// translations
// ---------------------------------------------------------------------------

it('translations returns a collection of all translation records', function () {
    $t = makeFacadeTranslation();

    $all = Lingua::translations();
    expect($all)->not->toBeEmpty()
        ->and($all->first())->toBeInstanceOf(Translation::class);

    $t->delete();
});

// ---------------------------------------------------------------------------
// getTranslations
// ---------------------------------------------------------------------------

it('getTranslations returns all locale values for a given key', function () {
    $t = makeFacadeTranslation('grp', 'welcome', ['en' => 'Hello', 'it' => 'Ciao']);

    $result = Lingua::getTranslations('welcome');
    expect($result)->toBeArray()
        ->and($result['en'])->toBe('Hello')
        ->and($result['it'])->toBe('Ciao');

    $t->delete();
});

it('getTranslations returns empty array for a missing key', function () {
    expect(Lingua::getTranslations('non_existent_key_xyz'))->toBe([]);
});

// ---------------------------------------------------------------------------
// getTranslation
// ---------------------------------------------------------------------------

it('getTranslation returns the value for the current locale', function () {
    $t = makeFacadeTranslation('grp', 'hello_current', ['en' => 'Hello']);
    app()->setLocale('en');

    expect(Lingua::getTranslation('hello_current'))->toBe('Hello');

    $t->delete();
});

it('getTranslation returns the value for an explicit locale', function () {
    $t = makeFacadeTranslation('grp', 'hello_explicit', ['en' => 'Hello', 'de' => 'Hallo']);

    expect(Lingua::getTranslation('hello_explicit', 'de'))->toBe('Hallo');

    $t->delete();
});

it('getTranslation returns empty string for a missing locale', function () {
    $t = makeFacadeTranslation('grp', 'hello_missing_locale', ['en' => 'Hello']);

    expect(Lingua::getTranslation('hello_missing_locale', 'zz'))->toBe('');

    $t->delete();
});

it('getTranslation returns empty string for an unknown key', function () {
    expect(Lingua::getTranslation('totally_unknown_key_abc', 'en'))->toBe('');
});

// ---------------------------------------------------------------------------
// setTranslation
// ---------------------------------------------------------------------------

it('setTranslation saves a new value for an existing key', function () {
    $t = makeFacadeTranslation('grp', 'set_test', ['en' => 'Original']);

    Lingua::setTranslation('set_test', 'Updated', 'en');

    $t->refresh();
    expect($t->text['en'])->toBe('Updated');

    $t->delete();
});

it('setTranslation adds a translation for a new locale', function () {
    Language::factory()->create(['code' => 'ja', 'is_default' => false]);
    $t = makeFacadeTranslation('grp', 'set_locale_test', ['en' => 'Hello']);

    Lingua::setTranslation('set_locale_test', 'こんにちは', 'ja');

    $t->refresh();
    expect($t->text['ja'])->toBe('こんにちは');

    $t->delete();
    Language::where('code', 'ja')->delete();
});

it('setTranslation uses current locale when none is provided', function () {
    app()->setLocale('en');
    $t = makeFacadeTranslation('grp', 'set_current_locale', ['en' => 'Old']);

    Lingua::setTranslation('set_current_locale', 'New');

    $t->refresh();
    expect($t->text['en'])->toBe('New');

    $t->delete();
});

// ---------------------------------------------------------------------------
// forgetTranslation
// ---------------------------------------------------------------------------

it('forgetTranslation removes the locale entry for a non-default locale', function () {
    Language::factory()->create(['code' => 'sv', 'is_default' => false]);
    $t = makeFacadeTranslation('grp', 'forget_test', ['en' => 'Hello', 'sv' => 'Hej']);

    Lingua::forgetTranslation('forget_test', 'sv');

    $t->refresh();
    expect(array_key_exists('sv', $t->text))->toBeFalse()
        ->and(array_key_exists('en', $t->text))->toBeTrue();

    $t->delete();
    Language::where('code', 'sv')->delete();
});

it('forgetTranslation deletes the entire record when locale is default', function () {
    $t = makeFacadeTranslation('grp', 'forget_default', ['en' => 'To be deleted']);
    $id = $t->id;

    Lingua::forgetTranslation('forget_default', 'en');

    expect(Translation::find($id))->toBeNull();
});

it('forgetTranslation uses current locale when none is provided', function () {
    Language::factory()->create(['code' => 'ko', 'is_default' => false]);
    $t = makeFacadeTranslation('grp', 'forget_current', ['en' => 'Hi', 'ko' => '안녕']);

    app()->setLocale('ko');
    Lingua::forgetTranslation('forget_current');

    $t->refresh();
    expect(array_key_exists('ko', $t->text))->toBeFalse();

    $t->delete();
    Language::where('code', 'ko')->delete();
    app()->setLocale('en');
});

// ---------------------------------------------------------------------------
// getTranslationByGroup
// ---------------------------------------------------------------------------

it('getTranslationByGroup returns all translations for a group', function () {
    $t1 = makeFacadeTranslation('widgets', 'title', ['en' => 'Widget Title']);
    $t2 = makeFacadeTranslation('widgets', 'body', ['en' => 'Widget Body']);

    $result = Lingua::getTranslationByGroup('widgets');
    expect($result)->toHaveCount(2);

    $t1->delete();
    $t2->delete();
});

it('getTranslationByGroup filtered by locale only returns translated entries', function () {
    Language::factory()->create(['code' => 'pl', 'is_default' => false]);

    $t1 = makeFacadeTranslation('buttons', 'save', ['en' => 'Save', 'pl' => 'Zapisz']);
    $t2 = makeFacadeTranslation('buttons', 'cancel', ['en' => 'Cancel']);

    $result = Lingua::getTranslationByGroup('buttons', 'pl');
    $keys = $result->pluck('key');

    expect($keys)->toContain('save')
        ->and($keys)->not->toContain('cancel');

    $t1->delete();
    $t2->delete();
    Language::where('code', 'pl')->delete();
});

it('getTranslationByGroup returns empty collection for unknown group', function () {
    $result = Lingua::getTranslationByGroup('nonexistent_group_xyz');
    expect($result)->toBeEmpty();
});

// ---------------------------------------------------------------------------
// getLocaleStats
// ---------------------------------------------------------------------------

it('getLocaleStats returns array with required keys', function () {
    $stats = Lingua::getLocaleStats('en');

    expect($stats)->toBeArray()
        ->toHaveKeys(['total', 'translated', 'missing', 'percentage']);
});

it('getLocaleStats translated count is at most total', function () {
    $stats = Lingua::getLocaleStats('en');

    expect($stats['translated'])->toBeLessThanOrEqual($stats['total'])
        ->and($stats['missing'])->toBe($stats['total'] - $stats['translated'])
        ->and($stats['percentage'])->toBeFloat();
});

it('getLocaleStats uses current app locale when none is provided', function () {
    app()->setLocale('en');
    $explicit = Lingua::getLocaleStats('en');
    $implicit = Lingua::getLocaleStats();

    expect($implicit['total'])->toBe($explicit['total'])
        ->and($implicit['translated'])->toBe($explicit['translated']);
});

it('getLocaleStats shows zero translated for a locale with no translations', function () {
    $stats = Lingua::getLocaleStats('zz');

    expect($stats['translated'])->toBe(0)
        ->and($stats['percentage'])->toBe(0.0);
});

// ---------------------------------------------------------------------------
// addLanguage / removeLanguage
// ---------------------------------------------------------------------------

it('addLanguage installs language files without throwing', function () {
    expect(fn () => Lingua::addLanguage('fr'))->not->toThrow(Exception::class);

    // Cleanup
    Artisan::call('lang:rm fr --force');
    Language::where('code', 'fr')->delete();
});

it('removeLanguage removes language files without throwing', function () {
    Artisan::call('lang:add fr');

    expect(fn () => Lingua::removeLanguage('fr'))->not->toThrow(Exception::class);
});

// ---------------------------------------------------------------------------
// syncToDatabase / syncToLocal
// ---------------------------------------------------------------------------

it('syncToDatabase can be called without throwing', function () {
    expect(fn () => Lingua::syncToDatabase())->not->toThrow(Exception::class);
});

it('syncToLocal can be called without throwing', function () {
    expect(fn () => Lingua::syncToLocal())->not->toThrow(Exception::class);
});

// ---------------------------------------------------------------------------
// get
// ---------------------------------------------------------------------------

it('get returns the Language model for an installed locale', function () {
    $language = Lingua::get('en');
    expect($language)->toBeInstanceOf(Language::class)
        ->and($language->code)->toBe('en');
});

it('get returns null for an unknown locale', function () {
    expect(Lingua::get('xx'))->toBeNull();
});

// ---------------------------------------------------------------------------
// getDefault
// ---------------------------------------------------------------------------

it('getDefault returns the Language model marked as default', function () {
    $language = Lingua::getDefault();
    expect($language)->toBeInstanceOf(Language::class)
        ->and($language->is_default)->toBeTrue();
});

it('getDefault code matches getDefaultLocale', function () {
    expect(Lingua::getDefault()->code)->toBe(Lingua::getDefaultLocale());
});

// ---------------------------------------------------------------------------
// getFallback
// ---------------------------------------------------------------------------

it('getFallback returns a Language model for the app fallback locale', function () {
    $fallback = Lingua::getFallback();
    expect($fallback)->toBeInstanceOf(Language::class)
        ->and($fallback->code)->toBe(app()->getFallbackLocale());
});

// ---------------------------------------------------------------------------
// installed / notInstalled / isInstalled / isAvailable
// ---------------------------------------------------------------------------

it('installed returns an array of locale codes', function () {
    $installed = Lingua::installed();
    expect($installed)->toBeArray()
        ->and($installed)->toContain('en');
});

it('notInstalled returns an array of available but uninstalled locale codes', function () {
    $notInstalled = Lingua::notInstalled();
    expect($notInstalled)->toBeArray();

    foreach (Lingua::installed() as $code) {
        expect($notInstalled)->not->toContain($code);
    }
});

it('notInstalled is sorted alphabetically', function () {
    $notInstalled = Lingua::notInstalled();
    $sorted = $notInstalled;
    sort($sorted);
    expect($notInstalled)->toBe($sorted);
});

it('isInstalled returns true for an installed locale', function () {
    expect(Lingua::isInstalled('en'))->toBeTrue();
});

it('isInstalled returns false for an unknown locale', function () {
    expect(Lingua::isInstalled('xx'))->toBeFalse();
});

it('isInstalled returns false for null', function () {
    expect(Lingua::isInstalled(null))->toBeFalse();
});

it('isAvailable returns true for an available but uninstalled locale', function () {
    $locale = Lingua::notInstalled()[0] ?? null;
    if ($locale === null) {
        $this->markTestSkipped('All locales are already installed.');
    }
    expect(Lingua::isAvailable($locale))->toBeTrue();
});

it('isAvailable returns false for an already installed locale', function () {
    expect(Lingua::isAvailable('en'))->toBeFalse();
});

it('isAvailable returns false for null', function () {
    expect(Lingua::isAvailable(null))->toBeFalse();
});
