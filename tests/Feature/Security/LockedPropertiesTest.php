<?php

declare(strict_types=1);

use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Rivalex\Lingua\Livewire\HeadlessLanguageSelector;
use Rivalex\Lingua\Livewire\Language\Delete as LanguageDelete;
use Rivalex\Lingua\Livewire\Languages;
use Rivalex\Lingua\Livewire\LanguageSelector;
use Rivalex\Lingua\Livewire\Selector\Icon;
use Rivalex\Lingua\Livewire\Settings;
use Rivalex\Lingua\Livewire\Translation\Create;
use Rivalex\Lingua\Livewire\Translation\Delete;
use Rivalex\Lingua\Livewire\Translation\Row;
use Rivalex\Lingua\Livewire\Translation\Update;
use Rivalex\Lingua\Livewire\Translations;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\Tests\Fixtures\LocaleSwitchProbe;

const LOCKED_IDENTITY = 'test|locked_key|0|';

// ---------------------------------------------------------------------------
// Locked properties cannot be written from the client
// ---------------------------------------------------------------------------

it('Translation Update locks its server-owned properties', function (string $property, mixed $value): void {
    expect(fn () => Livewire::test(Update::class, ['translationIdentity' => LOCKED_IDENTITY, 'currentLocale' => 'en'])
        ->set($property, $value))->toThrow(CannotUpdateLockedPropertyException::class);
})->with([
    ['translationIdentity', 'other|key|0|'],
    ['currentLocale', 'it'],
    ['translationsTypes', []],
    ['groups', []],
    ['required', true],
    ['locked', true],
    ['isVendor', true],
    ['fileMode', true],
]);

it('Translation Row locks its server-owned properties', function (string $property, mixed $value): void {
    expect(fn () => Livewire::test(Row::class, ['translationIdentity' => LOCKED_IDENTITY, 'currentLocale' => 'en'])
        ->set($property, $value))->toThrow(CannotUpdateLockedPropertyException::class);
})->with([
    ['currentLocale', 'it'],
    ['translationIdentity', 'other|key|0|'],
    ['key', 'other'],
    ['defaultValue', 'x'],
    ['editModalName', 'x'],
    ['deleteModalName', 'x'],
    ['translationType', 'html'],
    ['fileMode', true],
]);

it('Translation Delete locks its server-owned properties', function (string $property, mixed $value): void {
    expect(fn () => Livewire::test(Delete::class, ['translationIdentity' => LOCKED_IDENTITY, 'currentLocale' => 'en'])
        ->set($property, $value))->toThrow(CannotUpdateLockedPropertyException::class);
})->with([
    ['currentLocale', 'it'],
    ['localName', 'x'],
    ['translationIdentity', 'other|key|0|'],
    ['deleteAction', 'x'],
    ['deleteHeader', 'x'],
    ['isDefaultLocale', true],
    ['confirm', 'x'],
]);

it('Translation Create locks its server-owned properties', function (string $property, mixed $value): void {
    expect(fn () => Livewire::test(Create::class)->set($property, $value))
        ->toThrow(CannotUpdateLockedPropertyException::class);
})->with([
    ['translationsTypes', []],
    ['groups', []],
    ['fileMode', true],
]);

it('Language Delete locks confirm', function (): void {
    $language = Language::factory()->create(['code' => 'af', 'is_default' => false]);

    expect(fn () => Livewire::test(LanguageDelete::class, ['language' => $language])->set('confirm', 'x'))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

it('Languages locks fileMode', function (): void {
    expect(fn () => Livewire::test(Languages::class)->set('fileMode', true))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

it('Selector Icon locks its server-owned properties', function (string $property, mixed $value): void {
    expect(fn () => Livewire::test(Icon::class, ['locale' => 'en'])->set($property, $value))
        ->toThrow(CannotUpdateLockedPropertyException::class);
})->with([
    ['locale', 'it'],
    ['showFlags', false],
    ['square', true],
    ['flagClass', 'x'],
    ['size', 'lg'],
    ['customIcon', 'x'],
    ['textIconClasses', ['x']],
]);

it('Translations locks language', function (): void {
    expect(fn () => Livewire::test(Translations::class)->set('language', null))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

it('Translations locks availableLocale and availableGroups', function (string $property, array $value): void {
    expect(fn () => Livewire::test(Translations::class)->set($property, $value))
        ->toThrow(CannotUpdateLockedPropertyException::class);
})->with([
    ['availableLocale', ['zz' => 'x']],
    ['availableGroups', ['evil']],
]);

it('the language selectors lock currentUrl and currentLocale', function (string $component, string $property): void {
    expect(fn () => Livewire::test($component)->set($property, '/x'))
        ->toThrow(CannotUpdateLockedPropertyException::class);
})->with([
    [LanguageSelector::class, 'currentUrl'],
    [HeadlessLanguageSelector::class, 'currentUrl'],
    [LanguageSelector::class, 'currentLocale'],
    [HeadlessLanguageSelector::class, 'currentLocale'],
]);

// ---------------------------------------------------------------------------
// Actions recompute what they trust
// ---------------------------------------------------------------------------

it('Update aborts 404 for a key that does not exist and writes nothing (database mode)', function (): void {
    $before = Translation::count();

    Livewire::test(Update::class, ['translationIdentity' => 'ghost|missing_key|0|', 'currentLocale' => 'en'])
        ->set('textValue', 'pwned')
        ->call('updateTranslation')
        ->assertNotFound();

    expect(Translation::count())->toBe($before);
});

it('Update aborts 404 for a key that does not exist and writes no vendor file (file mode)', function (): void {
    $dir = sys_get_temp_dir().'/locked_props_'.uniqid();
    mkdir($dir.'/en', 0755, true);
    file_put_contents($dir.'/en/messages.php', '<?php return ["hello" => "Hello"];');
    config(['lingua.storage.driver' => 'file', 'lingua.lang_dir' => $dir]);

    try {
        Livewire::test(Update::class, ['translationIdentity' => 'pkg|missing_key|1|acme', 'currentLocale' => 'en'])
            ->set('textValue', 'pwned')
            ->call('updateTranslation')
            ->assertNotFound();

        expect(is_dir($dir.'/vendor'))->toBeFalse();
    } finally {
        unlink($dir.'/en/messages.php');
        rmdir($dir.'/en');
        rmdir($dir);
    }
});

it('Update aborts 404 for a locale that is not installed', function (): void {
    $translation = Translation::factory()->create([
        'group' => 'test', 'key' => 'locked_locale_key', 'type' => 'text',
        'text' => ['en' => 'Original'], 'is_vendor' => false, 'vendor' => null,
    ]);

    Livewire::test(Update::class, ['translationIdentity' => 'test|locked_locale_key|0|', 'currentLocale' => 'zz'])
        ->set('textValue', 'pwned')
        ->call('updateTranslation')
        ->assertNotFound();

    expect($translation->fresh()->text)->toBe(['en' => 'Original']);
});

it('Update still saves a valid translation', function (): void {
    $translation = Translation::factory()->create([
        'group' => 'test', 'key' => 'locked_valid_key', 'type' => 'text',
        'text' => ['en' => 'Original'], 'is_vendor' => false, 'vendor' => null,
    ]);

    Livewire::test(Update::class, ['translationIdentity' => 'test|locked_valid_key|0|', 'currentLocale' => 'en'])
        ->set('textValue', 'Changed')
        ->call('updateTranslation')
        ->assertHasNoErrors();

    expect($translation->fresh()->text['en'])->toBe('Changed');
});

it('Row aborts 404 for a key that does not exist and writes nothing', function (): void {
    $before = Translation::count();

    Livewire::test(Row::class, ['translationIdentity' => 'ghost|missing_key|0|', 'currentLocale' => 'en'])
        ->set('value', 'pwned')
        ->assertNotFound();

    expect(Translation::count())->toBe($before);
});

it('Translation Delete aborts 404 for a key that does not exist', function (): void {
    Livewire::test(Delete::class, ['translationIdentity' => 'ghost|missing_key|0|', 'currentLocale' => 'en'])
        ->set('control', 'X')
        ->call('deleteTranslation')
        ->assertNotFound();
});

// ---------------------------------------------------------------------------
// Open redirect hardening (ManagesLocale::changeLocale)
// ---------------------------------------------------------------------------

it('changeLocale neutralises a url with a control character to /', function (string $url): void {
    Language::factory()->create(['code' => 'it', 'is_default' => false]);

    Livewire::test(LocaleSwitchProbe::class, ['url' => $url])
        ->call('changeLocale', 'it')
        ->assertRedirect('/');
})->with([
    'tab after slash' => ["/\t/evil.example"],
    'newline' => ["/ok\n/evil.example"],
    'delete character' => ["/ok\x7F"],
]);

it('changeLocale keeps a regular relative url', function (): void {
    Language::factory()->create(['code' => 'it', 'is_default' => false]);

    Livewire::test(LocaleSwitchProbe::class, ['url' => '/lingua/translations?q=a%20b'])
        ->call('changeLocale', 'it')
        ->assertRedirect('/lingua/translations?q=a%20b');
});

// ---------------------------------------------------------------------------
// Bound properties keep a server-side check
// ---------------------------------------------------------------------------

it('Translations ignores an unknown currentLocale and keeps the previous one', function (): void {
    Livewire::test(Translations::class)
        ->set('currentLocale', 'zz')
        ->assertNoRedirect()
        ->assertSet('currentLocale', linguaDefaultLocale());
});

it('Translations still redirects for a known currentLocale', function (): void {
    Language::factory()->create(['code' => 'it', 'is_default' => false]);

    Livewire::test(Translations::class)
        ->set('currentLocale', 'it')
        ->assertRedirect();
});

it('Translations validates currentLocale against the database, not a snapshot taken at mount', function (): void {
    $component = Livewire::test(Translations::class);

    Language::factory()->create(['code' => 'it', 'is_default' => false]);

    $component->set('currentLocale', 'it')->assertRedirect();
});

it('Settings uiStickyTop rejects a css injection payload and accepts a plain length', function (): void {
    Livewire::test(Settings::class)
        ->set('uiStickyTop', '0; background:url(x)')
        ->assertHasErrors(['uiStickyTop']);

    Livewire::test(Settings::class)
        ->set('uiStickyTop', '4rem')
        ->assertHasNoErrors(['uiStickyTop']);
});
