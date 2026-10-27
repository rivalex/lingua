<?php

declare(strict_types=1);

use Livewire\Livewire;
use Rivalex\Lingua\Contracts\TranslationRepository;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Livewire\Language\Delete as LanguageDelete;
use Rivalex\Lingua\Livewire\Language\SetDefault;
use Rivalex\Lingua\Livewire\Translation\Delete as TranslationDelete;
use Rivalex\Lingua\Models\Language;

it('escapes a hostile key in the translation delete modal', function () {
    $payload = '<img src=x onerror=alert(1)>';

    $line = app(TranslationRepository::class)->create(
        'test',
        $payload,
        LinguaType::text,
        linguaDefaultLocale(),
        'value',
    );

    Livewire::test(TranslationDelete::class, [
        'translationIdentity' => $line->group.'|'.$line->key.'|0|',
        'currentLocale' => linguaDefaultLocale(),
    ])
        ->assertOk()
        ->assertDontSeeHtml('<img src=x onerror')
        ->assertSeeHtml('&lt;img src=x onerror=alert(1)&gt;');
});

it('escapes a hostile language name in the language delete modal', function () {
    $language = Language::factory()->create(['code' => 'xx', 'name' => '<b>x</b>', 'is_default' => false]);

    Livewire::test(LanguageDelete::class, ['language' => $language])
        ->assertOk()
        ->assertDontSeeHtml('<b>x</b>')
        ->assertSeeHtml('&lt;b&gt;x&lt;/b&gt;');
});

it('escapes a hostile language name in the set default modal', function () {
    $language = Language::factory()->create(['code' => 'xx', 'name' => '<b>x</b>', 'is_default' => false]);

    Livewire::test(SetDefault::class, ['language' => $language])
        ->assertOk()
        ->assertDontSeeHtml('<b>x</b>')
        ->assertSeeHtml('&lt;b&gt;x&lt;/b&gt;');
});
