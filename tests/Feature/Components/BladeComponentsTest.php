<?php

use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Rivalex\Lingua\Livewire\Translation\Create;
use Rivalex\Lingua\Livewire\Translation\Row;
use Rivalex\Lingua\Models\Language;
use Rivalex\Lingua\Models\Translation;

// The editor (text mode), message, clipboard, language-flag and menu-group components do not
// require a Livewire context and can be rendered with bare Blade::render().
// The editor (html/markdown) and autocomplete require @entangle inside a Livewire component,
// so they are tested through the Livewire components that embed them.

// Share an empty error bag so components that reference $errors don't throw.
beforeEach(function () {
    view()->share('errors', new \Illuminate\Support\ViewErrorBag);
});

// ─────────────────────────────────────────────
// x-lingua::message
// ─────────────────────────────────────────────

it('renders `x-lingua::message` with slot content', function () {
    $html = Blade::render(
        '<x-lingua::message on="test_event">Slot content here</x-lingua::message>'
    );

    expect($html)->toContain('Slot content here');
});

it('renders `x-lingua::message` wired to the correct `on` event', function () {
    $html = Blade::render(
        '<x-lingua::message on="my_event">Content</x-lingua::message>'
    );

    expect($html)->toContain('my_event');
});

it('renders `x-lingua::message` with custom `delay` prop (duration class changes)', function () {
    $html = Blade::render(
        '<x-lingua::message on="test_event" :delay="3000">Hello</x-lingua::message>'
    );

    // delay=3000 → $delay-500 = 2500
    expect($html)->toContain('2500ms');
});

it('renders `x-lingua::message` with default delay of 1500ms (2000-500)', function () {
    $html = Blade::render(
        '<x-lingua::message on="some_event">Default delay</x-lingua::message>'
    );

    expect($html)->toContain('1500ms');
});

// ─────────────────────────────────────────────
// x-lingua::clipboard
// ─────────────────────────────────────────────

it('renders `x-lingua::clipboard` with `textToCopy` prop', function () {
    $html = Blade::render(
        '<x-lingua::clipboard textToCopy="copy_this_text">Label text</x-lingua::clipboard>'
    );

    expect($html)->toContain('copy_this_text')
        ->and($html)->toContain('Label text');
});

it('renders `x-lingua::clipboard` with tooltip when `showTooltip` is true', function () {
    $html = Blade::render(
        '<x-lingua::clipboard textToCopy="copy_me" :showTooltip="true">Copy</x-lingua::clipboard>'
    );

    expect($html)->toContain('data-flux-tooltip');
});

it('renders `x-lingua::clipboard` without tooltip when `showTooltip` is false', function () {
    $html = Blade::render(
        '<x-lingua::clipboard textToCopy="copy_me" :showTooltip="false">Copy</x-lingua::clipboard>'
    );

    expect($html)->not->toContain('data-flux-tooltip');
});

// ─────────────────────────────────────────────
// x-lingua::editor (text mode — no @entangle)
// ─────────────────────────────────────────────

it('renders `x-lingua::editor` in `text` mode with a textarea', function () {
    $html = Blade::render(
        '<x-lingua::editor type="text" wire:model="textValue" placeholder="Enter text..." />'
    );

    expect($html)->toContain('data-flux-field')
        ->and($html)->toContain('textarea');
});

it('renders `x-lingua::editor` in `text` mode with a label', function () {
    $html = Blade::render(
        '<x-lingua::editor type="text" wire:model="textValue" label="My Label" />'
    );

    expect($html)->toContain('My Label');
});

it('renders `x-lingua::editor` with `required` badge when required is true', function () {
    $html = Blade::render(
        '<x-lingua::editor type="text" wire:model="textValue" label="My Label" :required="true" />'
    );

    expect($html)->toContain(__('lingua::lingua.global.required'));
});

it('renders `x-lingua::editor` text mode helper text', function () {
    $html = Blade::render(
        '<x-lingua::editor type="text" wire:model="textValue" />'
    );

    expect($html)->toContain(__('lingua::lingua.translations.editor.helper_text'));
});

// ─────────────────────────────────────────────
// x-lingua::editor (html/markdown) via Livewire
// These components use @entangle and require a Livewire context.
// ─────────────────────────────────────────────

it('renders `x-lingua::editor` in `html` mode inside a Livewire component', function () {
    $translation = Translation::create([
        'group' => 'test', 'key' => 'html_editor_'.uniqid(),
        'type' => 'html', 'text' => ['en' => '<p>Hello</p>'],
        'is_vendor' => false, 'vendor' => null,
    ]);

    Livewire::test(Row::class, ['translation' => $translation, 'currentLocale' => 'en'])
        ->assertStatus(200)
        ->assertSeeHtml('lingua-editor');

    $translation->delete();
});

it('renders `x-lingua::editor` in `markdown` mode inside a Livewire component', function () {
    $translation = Translation::create([
        'group' => 'test', 'key' => 'md_editor_'.uniqid(),
        'type' => 'markdown', 'text' => ['en' => '**Hello**'],
        'is_vendor' => false, 'vendor' => null,
    ]);

    Livewire::test(Row::class, ['translation' => $translation, 'currentLocale' => 'en'])
        ->assertStatus(200)
        ->assertSeeHtml('lingua-editor');

    $translation->delete();
});

// ─────────────────────────────────────────────
// x-lingua::autocomplete via Livewire
// Requires @entangle — tested through Translation/Create component.
// ─────────────────────────────────────────────

it('renders `x-lingua::autocomplete` inside `Translation/Create` Livewire component', function () {
    Livewire::test(Create::class)
        ->assertStatus(200)
        ->assertSeeHtml('autocomplete');
});

// ─────────────────────────────────────────────
// x-lingua::language-flag
// ─────────────────────────────────────────────

it('renders `x-lingua::language-flag` with name and description', function () {
    $html = Blade::render(
        '<x-lingua::language-flag name="English" description="English (US)" />'
    );

    expect($html)->toContain('English');
});

it('renders `x-lingua::language-flag` showing code and name together', function () {
    $html = Blade::render(
        '<x-lingua::language-flag code="en" name="English" description="English (US)" />'
    );

    expect($html)->toContain('English')
        ->and($html)->toContain('English (US)');
});

// ─────────────────────────────────────────────
// x-lingua::menu-group
// ─────────────────────────────────────────────

it('renders `x-lingua::menu-group` with heading', function () {
    $html = Blade::render(
        '<x-lingua::menu-group heading="My Group">Slot content</x-lingua::menu-group>'
    );

    expect($html)->toContain('My Group')
        ->and($html)->toContain('Slot content');
});

it('renders `x-lingua::menu-group` without heading (slot only)', function () {
    $html = Blade::render(
        '<x-lingua::menu-group>Just slot</x-lingua::menu-group>'
    );

    expect($html)->toContain('Just slot');
});
