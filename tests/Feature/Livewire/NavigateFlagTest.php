<?php

declare(strict_types=1);

use Livewire\Livewire;
use Rivalex\Lingua\Livewire\LanguageSelector;
use Rivalex\Lingua\Livewire\Translations;
use Rivalex\Lingua\Models\Language;

it('has navigate config set to false by default', function () {
    expect(config('lingua.navigate'))->toBeFalse();
});

it('changeLocale redirects when navigate is false', function () {
    config(['lingua.navigate' => false]);
    Language::factory()->create(['code' => 'it', 'is_default' => false]);

    $component = Livewire::test(LanguageSelector::class);
    $redirect = $component->currentUrl;

    $component->call('changeLocale', 'it')
        ->assertRedirect($redirect);

    Language::where('code', 'it')->delete();
});

it('changeLocale redirects when navigate is true', function () {
    config(['lingua.navigate' => true]);
    Language::factory()->create(['code' => 'it', 'is_default' => false]);

    $component = Livewire::test(LanguageSelector::class);
    $redirect = $component->currentUrl;

    $component->call('changeLocale', 'it')
        ->assertRedirect($redirect);

    Language::where('code', 'it')->delete();
});

it('changeLocale does not redirect with an unknown locale regardless of navigate flag', function () {
    foreach ([false, true] as $navigate) {
        config(['lingua.navigate' => $navigate]);

        Livewire::test(LanguageSelector::class)
            ->call('changeLocale', 'xx_FAKE')
            ->assertNoRedirect();
    }
});

it('Translations redirects on locale change when navigate is false', function () {
    config(['lingua.navigate' => false]);
    Language::factory()->create(['code' => 'fr', 'is_default' => false]);

    Livewire::test(Translations::class)
        ->set('currentLocale', 'fr')
        ->assertRedirect();

    Language::where('code', 'fr')->delete();
});

it('Translations redirects on locale change when navigate is true', function () {
    config(['lingua.navigate' => true]);
    Language::factory()->create(['code' => 'fr', 'is_default' => false]);

    Livewire::test(Translations::class)
        ->set('currentLocale', 'fr')
        ->assertRedirect();

    Language::where('code', 'fr')->delete();
});
