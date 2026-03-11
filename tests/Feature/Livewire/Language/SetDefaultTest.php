<?php

use LaravelLang\Locales\Facades\Locales;
use Livewire\Livewire;
use Rivalex\Lingua\Livewire\Language\SetDefault;
use Rivalex\Lingua\Models\Language;

it('can access `SetDefault` component', function () {
    $language = Language::first();
    Livewire::test(SetDefault::class, ['language' => $language])
            ->assertStatus(200)
            ->assertSee('Set as DEFAULT');
});

it('can SET default `language` and dispatch `events`', function () {
    $currentDefault = Language::where('is_default', true)->first();

    $newDefault = Language::factory()->create([
        'code' => 'it',
        'is_default' => false,
    ]);

    Livewire::test(SetDefault::class, ['language' => $newDefault])
            ->call('setDefaultLanguage')
            ->assertDispatched('language_default_set')
            ->assertDispatched('refreshLanguageRows');

    $newDefault->refresh();
    expect($newDefault->is_default)->toBeTrue();

    if ($currentDefault) {
        $currentDefault->refresh();
        expect($currentDefault->is_default)->toBeFalse();
    }

    Language::where('code', 'it')->delete();
});

it('catch `ERRORS` setting default Language for `Language::setDefault($locale)`', function () {
    $this->mock(Language::class, function ($mock) {
        $mock->shouldReceive('setDefault')
             ->once()
             ->andThrow(new Exception('Error setting language as default.'));
    });

    $newDefault = Language::factory()->create([
        'code' => 'it',
        'is_default' => false,
    ]);

    Livewire::test(SetDefault::class, ['language' => $newDefault])
            ->call('setDefaultLanguage')
            ->assertHasErrors(['setDefaultLanguage' => 'Error setting language as default.'])
            ->assertDispatched('language_default_fail');

    Language::where('code', 'it')->delete();
});
