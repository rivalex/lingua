<?php

use Livewire\Livewire;
use Rivalex\Lingua\Exceptions\VendorTranslationProtectedException;
use Rivalex\Lingua\Facades\Lingua;
use Rivalex\Lingua\Livewire\Translation\Create;
use Rivalex\Lingua\Livewire\Translation\Delete;
use Rivalex\Lingua\Livewire\Translation\Row;
use Rivalex\Lingua\Livewire\Translation\Update;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Recursively remove a directory and all its contents.
 */
function rrmdir(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . '/' . $item;
        is_dir($path) ? rrmdir($path) : @unlink($path);
    }
    @rmdir($dir);
}

/**
 * Clean up all files that syncToLocal writes into the test lang directory.
 * Called before every test so leftover files from a previous run don't bleed
 * into the next test's seeder invocation.
 */
beforeEach(function () {
    $langDir = config('lingua.lang_dir');

    // Remove vendor subtree written by syncToLocal
    rrmdir($langDir . '/vendor');

    // Remove any locale-specific JSON files / directories that syncToLocal may have written
    foreach (glob($langDir . '/*.json') ?: [] as $jsonFile) {
        // Keep the core en.json if it was part of the original fixture; remove all others
        if (basename($jsonFile) !== 'en.json') {
            @unlink($jsonFile);
        }
    }
    foreach (glob($langDir . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
        if (basename($dir) !== 'en') {
            rrmdir($dir);
        }
    }
});

function makeVendorTranslation(
    string $vendor = 'acme',
    string $group = 'messages',
    ?string $key = null,
    array $text = ['en' => 'Vendor English value']
): Translation {
    return Translation::create([
        'group'     => $group,
        'key'       => $key ?? 'vk_' . uniqid(),
        'type'      => 'text',
        'text'      => $text,
        'is_vendor' => true,
        'vendor'    => $vendor,
    ]);
}

// ---------------------------------------------------------------------------
// 1. Bidirectional sync — vendor PHP files
// ---------------------------------------------------------------------------

it('syncToDatabase imports vendor PHP translation files', function () {
    $langDir = config('lingua.lang_dir');
    $vendorDir = $langDir . '/vendor/testpkg/en';
    @mkdir($vendorDir, 0755, true);
    file_put_contents($vendorDir . '/alerts.php', "<?php\nreturn ['saved' => 'Record saved.'];");

    Lingua::syncToDatabase();

    $translation = Translation::where('is_vendor', true)
        ->where('vendor', 'testpkg')
        ->where('group', 'alerts')
        ->where('key', 'saved')
        ->first();

    expect($translation)->not->toBeNull()
        ->and($translation->text['en'])->toBe('Record saved.');

    // Cleanup
    Translation::where('vendor', 'testpkg')->delete();
    @unlink($vendorDir . '/alerts.php');
    @rmdir($vendorDir);
    @rmdir(dirname($vendorDir));
    @rmdir(dirname($vendorDir, 2));
});

it('syncToDatabase imports vendor JSON translation files', function () {
    $langDir = config('lingua.lang_dir');
    $vendorDir = $langDir . '/vendor/jsonpkg';
    @mkdir($vendorDir, 0755, true);
    file_put_contents($vendorDir . '/en.json', json_encode(['Goodbye' => 'Goodbye!']));

    Lingua::syncToDatabase();

    $translation = Translation::where('is_vendor', true)
        ->where('vendor', 'jsonpkg')
        ->where('group', 'single')
        ->where('key', 'Goodbye')
        ->first();

    expect($translation)->not->toBeNull()
        ->and($translation->text['en'])->toBe('Goodbye!');

    // Cleanup
    Translation::where('vendor', 'jsonpkg')->delete();
    @unlink($vendorDir . '/en.json');
    @rmdir($vendorDir);
    @rmdir(dirname($vendorDir));
});

it('syncToLocal writes vendor PHP translation files', function () {
    Language::factory()->create(['code' => 'fr', 'is_default' => false]);
    // Use a unique vendor name so leftover disk state from prior runs can never conflict.
    $vendor = 'mypkg_' . uniqid();
    $translation = makeVendorTranslation($vendor, 'buttons', 'save', ['en' => 'Save', 'fr' => 'Enregistrer']);

    Lingua::syncToLocal();

    $langDir = config('lingua.lang_dir');
    $expectedFile = $langDir . '/vendor/' . $vendor . '/fr/buttons.php';

    expect(file_exists($expectedFile))->toBeTrue();
    $content = include $expectedFile;
    expect($content['save'])->toBe('Enregistrer');

    // Cleanup DB + written files
    $translation->delete();
    Language::where('code', 'fr')->delete();
    @unlink($expectedFile);
    @rmdir(dirname($expectedFile));
    @rmdir(dirname($expectedFile, 2));
    @rmdir(dirname($expectedFile, 3));
});

it('syncToLocal writes vendor JSON translation files for single-group vendor keys', function () {
    Language::factory()->create(['code' => 'de', 'is_default' => false]);
    $uniqueKey = 'VendorJsonSync_' . uniqid();
    $translation = makeVendorTranslation('jsonout', 'single', $uniqueKey, ['en' => 'Hello', 'de' => 'Hallo']);

    Lingua::syncToLocal();

    $langDir = config('lingua.lang_dir');
    $expectedFile = $langDir . '/vendor/jsonout/de.json';

    expect(file_exists($expectedFile))->toBeTrue();
    $content = json_decode(file_get_contents($expectedFile), true);
    expect($content[$uniqueKey])->toBe('Hallo');

    // Cleanup
    $translation->delete();
    Language::where('code', 'de')->delete();
    @unlink($expectedFile);
    @rmdir(dirname($expectedFile));
    @rmdir(dirname($expectedFile, 2));
});

it('vendor translations are flagged is_vendor=true after sync', function () {
    $langDir = config('lingua.lang_dir');
    $vendorDir = $langDir . '/vendor/flagpkg/en';
    @mkdir($vendorDir, 0755, true);
    file_put_contents($vendorDir . '/ui.php', "<?php\nreturn ['btn' => 'Click me'];");

    Lingua::syncToDatabase();

    $translation = Translation::where('vendor', 'flagpkg')->where('key', 'btn')->first();
    expect($translation)->not->toBeNull()
        ->and($translation->is_vendor)->toBeTrue()
        ->and($translation->vendor)->toBe('flagpkg');

    // Cleanup
    Translation::where('vendor', 'flagpkg')->delete();
    @unlink($vendorDir . '/ui.php');
    @rmdir($vendorDir);
    @rmdir(dirname($vendorDir));
    @rmdir(dirname($vendorDir, 2));
});

// ---------------------------------------------------------------------------
// 2. Cannot create vendor translations via the Create component
// ---------------------------------------------------------------------------

it('Create component always produces is_vendor=false records', function () {
    $group = Translation::where('is_vendor', false)->first()->group;

    Livewire::test(Create::class)
        ->set('group', $group)
        ->set('key', 'vendor_guard_test_' . uniqid())
        ->set('translationType', 'text')
        ->set('textValue', 'Some value')
        ->call('addNewTranslation')
        ->assertDispatched('translation_added');

    $created = Translation::where('key', 'like', 'vendor_guard_test_%')->latest()->first();
    expect($created)->not->toBeNull()
        ->and($created->is_vendor)->toBeFalse()
        ->and($created->vendor)->toBeNull();

    $created->delete();
});

it('Create component does not expose is_vendor as a settable property', function () {
    // Livewire throws if you try to set a property that is not declared on the component.
    // This proves the form never gives callers control over is_vendor.
    $component = Livewire::test(Create::class);
    expect(fn () => $component->set('is_vendor', true))->toThrow(Exception::class);
});

// ---------------------------------------------------------------------------
// 3. Cannot delete vendor translations
// ---------------------------------------------------------------------------

it('Delete component dispatches vendor_translation_protected for vendor records (default locale)', function () {
    $translation = makeVendorTranslation();

    Livewire::test(Delete::class, [
        'translation'   => $translation,
        'currentLocale' => linguaDefaultLocale(),
    ])
        ->call('deleteTranslation')
        ->assertDispatched('vendor_translation_protected')
        ->assertNotDispatched('translation_deleted');

    expect(Translation::find($translation->id))->not->toBeNull();

    $translation->delete();
});

it('Delete component dispatches vendor_translation_protected for vendor records (non-default locale)', function () {
    Language::factory()->create(['code' => 'it', 'is_default' => false]);
    $translation = makeVendorTranslation('acme', 'messages', null, ['en' => 'Hello', 'it' => 'Ciao']);

    Livewire::test(Delete::class, [
        'translation'   => $translation,
        'currentLocale' => 'it',
    ])
        ->call('deleteTranslation')
        ->assertDispatched('vendor_translation_protected')
        ->assertNotDispatched('translation_locale_deleted');

    $translation->refresh();
    expect(isset($translation->text['it']))->toBeTrue(); // locale NOT removed

    $translation->delete();
    Language::where('code', 'it')->delete();
});

it('Delete component still deletes non-vendor translations normally', function () {
    $translation = Translation::create([
        'group' => 'test', 'key' => 'del_non_vendor_' . uniqid(),
        'type' => 'text', 'text' => ['en' => 'Value'],
        'is_vendor' => false, 'vendor' => null,
    ]);
    $id = $translation->id;

    Livewire::test(Delete::class, [
        'translation'   => $translation,
        'currentLocale' => linguaDefaultLocale(),
    ])
        ->call('deleteTranslation')
        ->assertDispatched('translation_deleted')
        ->assertNotDispatched('vendor_translation_protected');

    expect(Translation::find($id))->toBeNull();
});

it('Facade forgetTranslation throws VendorTranslationProtectedException for vendor records', function () {
    $translation = makeVendorTranslation('acme', 'messages', 'protected_key');

    expect(fn () => Lingua::forgetTranslation('protected_key'))
        ->toThrow(VendorTranslationProtectedException::class);

    expect(Translation::find($translation->id))->not->toBeNull();

    $translation->delete();
});

it('Facade forgetTranslation still works for non-vendor translations', function () {
    $translation = Translation::create([
        'group' => 'test', 'key' => 'forget_non_vendor_' . uniqid(),
        'type' => 'text', 'text' => ['en' => 'Value'],
        'is_vendor' => false, 'vendor' => null,
    ]);
    $id = $translation->id;

    Lingua::forgetTranslation($translation->key, 'en'); // default locale → full delete

    expect(Translation::find($id))->toBeNull();
});

// ---------------------------------------------------------------------------
// 4. Can edit vendor translations — update value and add locales
// ---------------------------------------------------------------------------

it('Update component can save a new text value for a vendor translation', function () {
    $translation = makeVendorTranslation('acme', 'messages', null, ['en' => 'Original']);

    Livewire::test(Update::class, [
        'translation'   => $translation,
        'currentLocale' => 'en',
    ])
        ->set('textValue', 'Updated vendor value')
        ->call('updateTranslation')
        ->assertHasNoErrors()
        ->assertDispatched('translation_updated');

    $translation->refresh();
    expect($translation->text['en'])->toBe('Updated vendor value');

    $translation->delete();
});

it('Update component can add a new locale translation to a vendor record', function () {
    Language::factory()->create(['code' => 'es', 'is_default' => false]);
    $translation = makeVendorTranslation('acme', 'messages', null, ['en' => 'Hello']);

    Livewire::test(Update::class, [
        'translation'   => $translation,
        'currentLocale' => 'es',
    ])
        ->set('textValue', 'Hola')
        ->call('updateTranslation')
        ->assertHasNoErrors()
        ->assertDispatched('translation_updated');

    $translation->refresh();
    expect($translation->text['es'])->toBe('Hola');

    $translation->delete();
    Language::where('code', 'es')->delete();
});

it('Update component does NOT change group or key for vendor translations', function () {
    $translation = makeVendorTranslation('acme', 'original_group', 'original_key', ['en' => 'Value']);

    Livewire::test(Update::class, [
        'translation'   => $translation,
        'currentLocale' => 'en',
    ])
        ->set('group', 'hacked_group')
        ->set('key', 'hacked_key')
        ->set('textValue', 'New value')
        ->call('updateTranslation')
        ->assertHasNoErrors();

    $translation->refresh();
    expect($translation->group)->toBe('original_group')
        ->and($translation->key)->toBe('original_key');

    $translation->delete();
});

it('Update component exposes isVendor=true for vendor translations', function () {
    $translation = makeVendorTranslation();

    Livewire::test(Update::class, [
        'translation'   => $translation,
        'currentLocale' => 'en',
    ])->assertSet('isVendor', true);

    $translation->delete();
});

it('Update component exposes isVendor=false for non-vendor translations', function () {
    $translation = Translation::create([
        'group' => 'test', 'key' => 'isvend_non_' . uniqid(),
        'type' => 'text', 'text' => ['en' => 'Value'],
        'is_vendor' => false, 'vendor' => null,
    ]);

    Livewire::test(Update::class, [
        'translation'   => $translation,
        'currentLocale' => 'en',
    ])->assertSet('isVendor', false);

    $translation->delete();
});

it('Row component allows inline editing of a vendor translation value', function () {
    $translation = makeVendorTranslation('acme', 'messages', null, ['en' => 'Vendor text']);

    Livewire::test(Row::class, [
        'translation'   => $translation,
        'currentLocale' => 'en',
    ])
        ->set('value', 'Updated inline vendor text')
        ->assertHasNoErrors();

    $translation->refresh();
    expect($translation->text['en'])->toBe('Updated inline vendor text');

    $translation->delete();
});

it('Row component does NOT call forgetTranslation when value cleared on a vendor record', function () {
    Language::factory()->create(['code' => 'nl', 'is_default' => false]);
    $translation = makeVendorTranslation('acme', 'messages', null, ['en' => 'Hello', 'nl' => 'Hallo']);

    Livewire::test(Row::class, [
        'translation'   => $translation,
        'currentLocale' => 'nl',
    ])->set('value', ''); // clearing — should NOT remove the nl entry

    $translation->refresh();
    expect(isset($translation->text['nl']))->toBeTrue();

    $translation->delete();
    Language::where('code', 'nl')->delete();
});

// ---------------------------------------------------------------------------
// 5. Facade respects vendor translations
// ---------------------------------------------------------------------------

it('Facade getVendorTranslations returns only records for the given vendor', function () {
    $t1 = makeVendorTranslation('pkg_a', 'messages', 'k1', ['en' => 'A1']);
    $t2 = makeVendorTranslation('pkg_a', 'alerts', 'k2', ['en' => 'A2']);
    $t3 = makeVendorTranslation('pkg_b', 'messages', 'k3', ['en' => 'B1']);

    $results = Lingua::getVendorTranslations('pkg_a');
    $ids = $results->pluck('id');

    expect($ids)->toContain($t1->id)
        ->and($ids)->toContain($t2->id)
        ->and($ids)->not->toContain($t3->id);

    $t1->delete();
    $t2->delete();
    $t3->delete();
});

it('Facade getVendorTranslations filtered by locale returns only translated entries', function () {
    Language::factory()->create(['code' => 'pt', 'is_default' => false]);
    $t1 = makeVendorTranslation('mypkg', 'ui', 'btn_ok', ['en' => 'OK', 'pt' => 'OK']);
    $t2 = makeVendorTranslation('mypkg', 'ui', 'btn_cancel', ['en' => 'Cancel']);

    $results = Lingua::getVendorTranslations('mypkg', 'pt');
    $keys = $results->pluck('key');

    expect($keys)->toContain('btn_ok')
        ->and($keys)->not->toContain('btn_cancel');

    $t1->delete();
    $t2->delete();
    Language::where('code', 'pt')->delete();
});

it('Facade getVendorTranslations returns empty for unknown vendor', function () {
    expect(Lingua::getVendorTranslations('no_such_vendor_xyz'))->toBeEmpty();
});

it('Facade setVendorTranslation updates the value for an existing vendor record', function () {
    $translation = makeVendorTranslation('acme', 'messages', 'greet', ['en' => 'Hello']);

    Lingua::setVendorTranslation('acme', 'messages', 'greet', 'Hi there', 'en');

    $translation->refresh();
    expect($translation->text['en'])->toBe('Hi there');

    $translation->delete();
});

it('Facade setVendorTranslation adds a new locale to a vendor record', function () {
    Language::factory()->create(['code' => 'ru', 'is_default' => false]);
    $translation = makeVendorTranslation('acme', 'messages', 'greet_ru', ['en' => 'Hello']);

    Lingua::setVendorTranslation('acme', 'messages', 'greet_ru', 'Привет', 'ru');

    $translation->refresh();
    expect($translation->text['ru'])->toBe('Привет');

    $translation->delete();
    Language::where('code', 'ru')->delete();
});

it('Facade setVendorTranslation uses current locale when none provided', function () {
    app()->setLocale('en');
    $translation = makeVendorTranslation('acme', 'messages', 'greet_current', ['en' => 'Old']);

    Lingua::setVendorTranslation('acme', 'messages', 'greet_current', 'New');

    $translation->refresh();
    expect($translation->text['en'])->toBe('New');

    $translation->delete();
});

it('Facade setVendorTranslation throws ModelNotFoundException for unknown vendor record', function () {
    expect(fn () => Lingua::setVendorTranslation('ghost_pkg', 'group', 'key', 'value'))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

it('Facade getTranslation retrieves value from a vendor translation by key', function () {
    $translation = makeVendorTranslation('acme', 'messages', 'facade_vendor_get', ['en' => 'Vendor value']);

    expect(Lingua::getTranslation('facade_vendor_get', 'en'))->toBe('Vendor value');

    $translation->delete();
});

it('Facade getTranslations returns all locales including vendor ones', function () {
    $translation = makeVendorTranslation('acme', 'messages', 'facade_vendor_all', ['en' => 'Hello', 'fr' => 'Bonjour']);

    $result = Lingua::getTranslations('facade_vendor_all');
    expect($result)->toBeArray()
        ->and($result['en'])->toBe('Hello')
        ->and($result['fr'])->toBe('Bonjour');

    $translation->delete();
});
