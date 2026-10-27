<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Rivalex\Lingua\Models\Language;

/*
 * Livewire skips the boot hooks on a request that replays a lazy placeholder snapshot,
 * so the AuthorizesLingua trait must authorize in its call and updating hooks too.
 * Each test renders the real lazy placeholder, then posts its snapshot to the real
 * Livewire update endpoint, the way a browser (or an attacker) would.
 */

beforeEach(function (): void {
    $this->originalEnvironment = app()->environment();
    app()['env'] = 'production';

    // Only user 1 may manage Lingua.
    Gate::define('manage-lingua', fn ($user = null) => $user !== null && $user->getAuthIdentifier() === 1);
});

afterEach(function (): void {
    app()['env'] = $this->originalEnvironment;
});

/**
 * Render a lazy component and return the snapshot of its placeholder.
 */
function lazySnapshot(string $component): string
{
    $html = Blade::render('<livewire:'.$component.' lazy />');

    preg_match('/wire:snapshot="([^"]*)"/', $html, $matches);

    expect($matches)->toHaveCount(2, 'The lazy placeholder carries a snapshot.');

    return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Post a snapshot with calls and updates to the Livewire update endpoint.
 *
 * @param  array<int, array{method: string, params: array<int, mixed>, metadata?: array<string, mixed>}>  $calls
 * @param  array<string, mixed>  $updates
 */
function replayLazySnapshot(string $snapshot, array $calls = [], array $updates = []): TestResponse
{
    return test()->withSession(['_token' => 'lazy-token'])->postJson(Livewire::getUpdateUri(), [
        'components' => [[
            'snapshot' => $snapshot,
            'updates' => (object) $updates,
            'calls' => $calls,
        ]],
    ], ['X-Livewire' => 'true', 'X-CSRF-TOKEN' => 'lazy-token']);
}

test('a lazy placeholder snapshot replayed with a method call is forbidden for a denied user', function (): void {
    Language::factory()->create(['code' => 'it', 'is_default' => false]);
    Language::factory()->create(['code' => 'fr', 'is_default' => false]);
    $before = Language::orderBy('id')->pluck('sort', 'id')->all();
    $first = (int) array_key_first($before);

    $snapshot = lazySnapshot('lingua::language.sort');

    $this->actingAs(new GenericUser(['id' => 2]));

    replayLazySnapshot($snapshot, [
        ['method' => 'updateLanguageOrder', 'params' => [$first, 2], 'metadata' => (object) []],
    ])->assertForbidden();

    expect(Language::orderBy('id')->pluck('sort', 'id')->all())->toBe($before);
});

test('a lazy placeholder snapshot replayed with a method call is accepted for an allowed user', function (): void {
    $snapshot = lazySnapshot('lingua::language.sort');

    $this->actingAs(new GenericUser(['id' => 1]));

    replayLazySnapshot($snapshot, [
        ['method' => 'updateLanguageOrder', 'params' => [0, 0], 'metadata' => (object) []],
    ])->assertOk();
});

test('the sync action of a lazy page component is forbidden for a denied user and accepted for an allowed one', function (): void {
    $snapshot = lazySnapshot('lingua::languages');

    $this->actingAs(new GenericUser(['id' => 2]));
    replayLazySnapshot($snapshot, [
        ['method' => 'syncToDatabase', 'params' => [], 'metadata' => (object) []],
    ])->assertForbidden();

    $this->actingAs(new GenericUser(['id' => 1]));
    replayLazySnapshot($snapshot, [
        ['method' => 'syncToDatabase', 'params' => [], 'metadata' => (object) []],
    ])->assertOk();
});

test('a property update on a lazy component is forbidden for a denied user and accepted for an allowed one', function (): void {
    $snapshot = lazySnapshot('lingua::language.table');

    $this->actingAs(new GenericUser(['id' => 2]));
    replayLazySnapshot($snapshot, [], ['search' => 'it'])->assertForbidden();

    $this->actingAs(new GenericUser(['id' => 1]));
    replayLazySnapshot($snapshot, [], ['search' => 'it'])->assertOk();
});

test('a lazy placeholder snapshot replayed with no call and no update is forbidden for a denied user and returns no component data', function (): void {
    $snapshot = lazySnapshot('lingua::language.table');

    $this->actingAs(new GenericUser(['id' => 2]));
    $denied = replayLazySnapshot($snapshot);
    $denied->assertForbidden();
    expect($denied->getContent())->not->toContain('snapshot');

    $this->actingAs(new GenericUser(['id' => 1]));
    replayLazySnapshot($snapshot)->assertOk();
});
