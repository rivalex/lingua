<?php

declare(strict_types=1);

use Livewire\Livewire;
use Rivalex\Lingua\Livewire\Settings;
use Rivalex\Lingua\Livewire\Translation\Delete;
use Rivalex\Lingua\Livewire\Translations;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\LinguaSetting;
use Rivalex\Lingua\Models\Translation;

it('renders the settings page without the fields nothing reads', function (): void {
    $html = Livewire::test(Settings::class)->assertOk()->html();

    expect($html)
        ->toContain('wire:model.live="navEnabled"')
        ->toContain('wire:model="uiStickyTop"')
        ->not->toContain('wire:model="layout"')
        ->not->toContain('linksTranslationsRoute')
        ->not->toContain('wire:model.live="navigate"')
        ->not->toContain('editor.');
});

it('does not write the removed settings on save', function (): void {
    Livewire::test(Settings::class)->call('save')->assertHasNoErrors();

    $written = LinguaSetting::pluck('key')->all();

    expect($written)
        ->toContain(LinguaSetting::KEY_UI_STICKY_TOP)
        ->not->toContain(LinguaSetting::KEY_NAVIGATE)
        ->not->toContain(LinguaSetting::KEY_LAYOUT)
        ->not->toContain(LinguaSetting::KEY_LINKS_TRANSLATIONS_ENABLED)
        ->not->toContain(LinguaSetting::KEY_LINKS_TRANSLATIONS_ROUTE)
        ->not->toContain(LinguaSetting::KEY_EDITOR);
});

it('stores a decimal sticky top as the validated string', function (): void {
    Livewire::test(Settings::class)
        ->set('uiStickyTop', '4.5')
        ->call('save')
        ->assertHasNoErrors();

    expect(LinguaSetting::where('key', LinguaSetting::KEY_UI_STICKY_TOP)->value('value'))->toBe('4.5')
        ->and((string) LinguaSetting::get(LinguaSetting::KEY_UI_STICKY_TOP))->toBe('4.5');
});

it('renders a stored sticky top that is not a plain length as zero', function (): void {
    LinguaSetting::set(LinguaSetting::KEY_UI_STICKY_TOP, '0; background:red');

    $html = Livewire::test(Translations::class)->assertOk()->html();

    expect($html)
        ->toContain('top: 0;')
        ->not->toContain('background:red');
});

it('keeps a css string from the config when no sticky top is stored', function (): void {
    config(['lingua.ui.sticky_top' => 'var(--h)']);

    expect(Livewire::test(Translations::class)->html())->toContain('top: var(--h);');
});

it('renders a stored plain length as the sticky top', function (): void {
    LinguaSetting::set(LinguaSetting::KEY_UI_STICKY_TOP, '4.5');

    expect(Livewire::test(Translations::class)->html())->toContain('top: 4.5rem;');
});

it('renders the locale name in bold in the delete modal of a non-default locale', function (): void {
    Language::factory()->create(['code' => 'it', 'name' => 'Italian', 'is_default' => false]);
    Translation::factory()->create(['group' => 'test', 'key' => 'bold_modal_key']);

    $html = Livewire::test(Delete::class, [
        'translationIdentity' => 'test|bold_modal_key|0|',
        'currentLocale' => 'it',
    ])->assertOk()->html();

    expect($html)
        ->toContain('<strong>ITALIAN</strong>')
        ->toContain('<strong>Italian</strong>');
});

it('escapes the locale name in the delete modal', function (): void {
    Language::factory()->create(['code' => 'it', 'name' => '<i>x</i>', 'is_default' => false]);
    Translation::factory()->create(['group' => 'test', 'key' => 'escape_modal_key']);

    $html = Livewire::test(Delete::class, [
        'translationIdentity' => 'test|escape_modal_key|0|',
        'currentLocale' => 'it',
    ])->html();

    expect($html)
        ->not->toContain('<i>x</i>')
        ->not->toContain('<I>X</I>')
        ->toContain('&lt;I&gt;X&lt;/I&gt;');
});
