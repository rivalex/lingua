<?php

declare(strict_types=1);

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Rivalex\Lingua\Contracts\BaseTranslationSource;
use Rivalex\Lingua\Database\DatabaseRepository;
use Rivalex\Lingua\Enums\LinguaType;
use Rivalex\Lingua\Models\Translation;
use Rivalex\Lingua\TranslationManager\CacheKey;

function edgeRepository(): DatabaseRepository
{
    return new DatabaseRepository(app(BaseTranslationSource::class));
}

function edgeStore(): Repository
{
    return Cache::store(config('lingua.cache.store'));
}

it('clears the cache of the old group when a row is renamed to another group', function (): void {
    app()->setLocale('en');
    $row = Translation::factory()->core()->create([
        'group' => 'auditold',
        'key' => 'k',
        'type' => 'text',
        'text' => ['en' => 'Value'],
    ]);

    app('translator')->setLoaded([]);
    expect(__('auditold.k'))->toBe('Value');

    $repo = edgeRepository();
    $repo->updateMeta($repo->toLine($row), 'auditnew', 'k', LinguaType::text);

    app('translator')->setLoaded([]);

    expect(__('auditold.k'))->toBe('auditold.k')
        ->and(__('auditnew.k'))->toBe('Value');
});

it('forgets again after the commit when a read inside the transaction re-cached the value', function (): void {
    app()->setLocale('en');
    $row = Translation::factory()->core()->create([
        'group' => 'auditx',
        'key' => 'k',
        'type' => 'text',
        'text' => ['en' => 'OLD'],
    ]);
    $repo = edgeRepository();
    $line = $repo->toLine($row);

    DB::transaction(function () use ($repo, $line): void {
        $repo->setValue($line, 'en', 'NEW');

        app('translator')->setLoaded([]);
        expect(__('auditx.k'))->toBe('NEW');
    });

    expect(edgeStore()->get(CacheKey::forGroup('en', 'auditx')))->toBeNull();
});

it('does not cache a group that has no rows', function (): void {
    app()->setLocale('en');
    app('translator')->setLoaded([]);

    expect(__('attacker-x.y'))->toBe('attacker-x.y')
        ->and(edgeStore()->has(CacheKey::forGroup('en', 'attacker-x')))->toBeFalse();
});

it('serves validation rows whose keys contain dots and a wildcard', function (): void {
    app()->setLocale('en');
    Translation::factory()->core()->create([
        'group' => 'validation',
        'key' => 'custom.items.*.name.required',
        'type' => 'text',
        'text' => ['en' => 'DB MSG'],
    ]);
    Translation::factory()->core()->create([
        'group' => 'validation',
        'key' => 'attributes.address.street',
        'type' => 'text',
        'text' => ['en' => 'Street'],
    ]);
    app('translator')->setLoaded([]);

    $items = Validator::make(['items' => [['name' => '']]], ['items.*.name' => 'required']);
    $address = Validator::make(['address' => ['street' => '']], ['address.street' => 'required']);

    expect($items->errors()->first('items.0.name'))->toBe('DB MSG')
        ->and($address->errors()->first('address.street'))->toContain('Street');
});
